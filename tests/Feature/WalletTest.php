<?php //>

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use MatrixPlatform\Models\FrozenLog;
use MatrixPlatform\Models\Member;
use MatrixPlatform\Models\Wallet;
use MatrixPlatform\Models\WalletLog;
use Tests\FeatureTestCase;
use Tests\Models\TestMember;

class WalletTest extends FeatureTestCase {

    public function test_balance_and_frozen_default_to_zero() {
        $member = $this->member();
        $this->currency('USD');

        $wallet = Wallet::forceCreate([
            'currency_id' => \MatrixPlatform\Models\Currency::where('code', 'USD')->value('id'),
            'member_id' => $member->id,
        ]);

        $this->assertSame(0.0, $wallet->balance);
        $this->assertSame(0.0, $wallet->frozen);
    }

    public function test_balance_and_frozen_cast_to_float() {
        $wallet = $this->wallet(balance: 12.5, frozen: 3.25);

        $fresh = Wallet::find($wallet->id);

        $this->assertIsFloat($fresh->balance);
        $this->assertIsFloat($fresh->frozen);
        $this->assertSame(12.5, $fresh->balance);
        $this->assertSame(3.25, $fresh->frozen);
    }

    public function test_currency_relation_returns_currency() {
        $wallet = $this->wallet(code: 'USD');

        $this->assertSame('USD', Wallet::find($wallet->id)->currency->code);
    }

    public function test_member_relation_resolves_configured_model() {
        config(['wallet.member-model' => TestMember::class]);

        $member = $this->member();
        $wallet = $this->wallet(member: $member);

        $fresh = Wallet::find($wallet->id);

        $this->assertInstanceOf(TestMember::class, $fresh->member);
        $this->assertSame($member->id, $fresh->member->id);
    }

    public function test_member_relation_falls_back_to_base_member_when_configured() {
        config(['wallet.member-model' => Member::class]);

        $member = $this->member();
        $wallet = $this->wallet(member: $member);

        $fresh = Wallet::find($wallet->id);

        $this->assertSame(Member::class, get_class($fresh->member));
        $this->assertSame($member->id, $fresh->member->id);
    }

    public function test_logs_relation_returns_wallet_logs() {
        $wallet = $this->wallet(balance: 0);

        DB::transaction(fn () => $wallet->manipulate('deposit', 10));
        DB::transaction(fn () => $wallet->manipulate('deposit', 20));

        $logs = $wallet->logs()->orderBy('id')->get();

        $this->assertCount(2, $logs);
        $this->assertContainsOnlyInstancesOf(WalletLog::class, $logs);
        $this->assertSame(10.0, (float) $logs[0]->amount);
        $this->assertSame(20.0, (float) $logs[1]->amount);
    }

    public function test_logs_relation_excludes_frozen_logs() {
        $wallet = $this->wallet(balance: 100);

        DB::transaction(fn () => $wallet->freeze('hold', 30));

        $this->assertCount(0, $wallet->logs);
    }

    public function test_logs_relation_isolates_by_wallet() {
        $member = $this->member();
        $usd = $this->wallet(balance: 100, code: 'USD', member: $member);
        $twd = $this->wallet(balance: 100, code: 'TWD', member: $member);

        DB::transaction(fn () => $usd->manipulate('deposit', 10));
        DB::transaction(fn () => $twd->manipulate('deposit', 99));

        $this->assertCount(1, $usd->logs);
        $this->assertSame(10.0, (float) $usd->logs->first()->amount);
    }

    public function test_to_title_delegates_to_currency() {
        $wallet = $this->wallet();
        $wallet->currency->title = 'US Dollar';
        $wallet->currency->save();

        $this->assertSame('US Dollar', $wallet->fresh()->toTitle());
    }

    public function test_freeze_method_delegates_to_wallets_service() {
        $wallet = $this->wallet(balance: 100);

        $log = DB::transaction(fn () => $wallet->freeze('hold', 25, 'note', ['k' => 'v']));

        $this->assertInstanceOf(FrozenLog::class, $log);
        $this->assertSame($wallet->id, $log->wallet_id);
        $this->assertSame('hold', $log->type);
        $this->assertSame(25.0, (float) $log->amount);
        $this->assertSame('note', $log->remark);
        $this->assertSame(['k' => 'v'], $log->data);
    }

    public function test_manipulate_method_delegates_to_wallets_service() {
        $wallet = $this->wallet(balance: 100);

        $log = DB::transaction(fn () => $wallet->manipulate('deposit', 25, 'note', ['k' => 'v']));

        $this->assertInstanceOf(WalletLog::class, $log);
        $this->assertSame($wallet->id, $log->wallet_id);
        $this->assertSame('deposit', $log->type);
        $this->assertSame(25.0, (float) $log->amount);
        $this->assertSame(125.0, (float) $log->balance);
        $this->assertSame('note', $log->remark);
        $this->assertSame(['k' => 'v'], $log->data);
    }

    public function test_alias_returns_wallets() {
        $this->assertSame('wallets', (new Wallet)->getAlias());
    }

    public function test_parent_returns_member() {
        $this->assertSame('member', (new Wallet)->getParent());
    }

}
