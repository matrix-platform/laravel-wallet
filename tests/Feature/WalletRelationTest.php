<?php //>

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use MatrixPlatform\Models\Currency;
use MatrixPlatform\Models\FrozenLog;
use MatrixPlatform\Models\Member;
use MatrixPlatform\Models\Wallet;
use MatrixPlatform\Models\WalletLog;
use Tests\Models\TestMember;
use Tests\WalletFeatureTestCase;

class WalletRelationTest extends WalletFeatureTestCase {

    public function test_logs_returns_wallet_logs_in_creation_order() {
        $wallet = $this->wallet(balance: 0);

        DB::transaction(function () use ($wallet) {
            $wallet->manipulate('deposit', 10);
        });
        DB::transaction(function () use ($wallet) {
            $wallet->manipulate('deposit', 20);
        });
        DB::transaction(function () use ($wallet) {
            $wallet->manipulate('deposit', 30);
        });

        $logs = $wallet->logs()->orderBy('id')->get();

        $this->assertCount(3, $logs);
        $this->assertEquals(10, (float) $logs[0]->amount);
        $this->assertEquals(20, (float) $logs[1]->amount);
        $this->assertEquals(30, (float) $logs[2]->amount);
    }

    public function test_logs_excludes_other_wallets() {
        $member = $this->member();
        $usd = $this->wallet(balance: 100, code: 'USD', member: $member);
        $twd = $this->wallet(balance: 100, code: 'TWD', member: $member);

        DB::transaction(function () use ($usd) {
            $usd->manipulate('deposit', 10);
        });
        DB::transaction(function () use ($twd) {
            $twd->manipulate('deposit', 20);
        });

        $this->assertCount(1, $usd->logs);
        $this->assertEquals(10, (float) $usd->logs->first()->amount);
    }

    public function test_logs_excludes_frozen_logs() {
        $wallet = $this->wallet(balance: 100);

        DB::transaction(function () use ($wallet) {
            $wallet->freeze('order', 50);
        });

        $this->assertCount(0, $wallet->logs);
    }

    public function test_to_title_delegates_to_currency() {
        $wallet = $this->wallet(balance: 0);

        $this->assertEquals('USD', $wallet->toTitle());
        $this->assertEquals($wallet->currency->toTitle(), $wallet->toTitle());
    }

    public function test_wallet_log_belongs_to_wallet() {
        $wallet = $this->wallet(balance: 0);

        DB::transaction(function () use ($wallet) {
            $wallet->manipulate('deposit', 10);
        });

        $log = WalletLog::where('wallet_id', $wallet->id)->first();

        $this->assertEquals($wallet->id, $log->wallet->id);
    }

    public function test_frozen_log_belongs_to_wallet() {
        $wallet = $this->wallet(balance: 100);

        DB::transaction(function () use ($wallet) {
            $wallet->freeze('order', 30);
        });

        $log = FrozenLog::where('wallet_id', $wallet->id)->first();

        $this->assertEquals($wallet->id, $log->wallet->id);
    }

    public function test_wallet_log_data_cast_is_array() {
        $wallet = $this->wallet(balance: 0);

        DB::transaction(function () use ($wallet) {
            $wallet->manipulate('deposit', 10, null, ['ref' => 'r1', 'nested' => ['k' => 'v']]);
        });

        $log = WalletLog::find(WalletLog::where('wallet_id', $wallet->id)->value('id'));

        $this->assertIsArray($log->data);
        $this->assertEquals(['ref' => 'r1', 'nested' => ['k' => 'v']], $log->data);
    }

    public function test_frozen_log_data_cast_is_array() {
        $wallet = $this->wallet(balance: 100);

        DB::transaction(function () use ($wallet) {
            $wallet->freeze('order', 30, null, ['ref' => 'r1']);
        });

        $log = FrozenLog::find(FrozenLog::where('wallet_id', $wallet->id)->value('id'));

        $this->assertIsArray($log->data);
        $this->assertEquals(['ref' => 'r1'], $log->data);
    }

    public function test_currency_time_columns_cast_to_carbon() {
        $enable = now()->subHour();
        $disable = now()->addHour();
        $created = $this->currency('USD', enable: $enable, disable: $disable);

        $currency = Currency::find($created->id);

        $this->assertInstanceOf(Carbon::class, $currency->enable_time);
        $this->assertInstanceOf(Carbon::class, $currency->disable_time);
        $this->assertEqualsWithDelta($enable->timestamp, $currency->enable_time->timestamp, 1);
        $this->assertEqualsWithDelta($disable->timestamp, $currency->disable_time->timestamp, 1);
    }

    public function test_member_relation_defaults_to_base_member() {
        $member = $this->member();
        $wallet = $this->wallet(member: $member);
        $fresh = Wallet::find($wallet->id);

        $this->assertSame(Member::class, get_class($fresh->member));
        $this->assertEquals($member->id, $fresh->member->id);
    }

    public function test_member_relation_resolves_configured_subclass() {
        config(['wallet.member-model' => TestMember::class]);

        $member = $this->member();
        $wallet = $this->wallet(member: $member);
        $fresh = Wallet::find($wallet->id);

        $this->assertInstanceOf(TestMember::class, $fresh->member);
        $this->assertEquals($member->id, $fresh->member->id);
    }

}
