<?php //>

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use MatrixPlatform\Exceptions\ServiceException;
use MatrixPlatform\Models\FrozenLog;
use MatrixPlatform\Models\WalletLog;
use MatrixPlatform\Support\Wallets;
use Tests\FeatureTestCase;

class WalletConcurrencyTest extends FeatureTestCase {

    public function test_manipulate_throws_data_conflicted_when_balance_changed_externally() {
        $wallet = $this->wallet(balance: 100);

        try {
            DB::transaction(function () use ($wallet) {
                DB::table('base_wallet')->where('id', $wallet->id)->update(['balance' => 999]);
                $wallet->manipulate('deposit', 10);
            });
            $this->fail('expected ServiceException was not thrown');
        } catch (ServiceException $e) {
            $this->assertSame('data-conflicted', $e->getError());
            $this->assertSame(0, WalletLog::where('wallet_id', $wallet->id)->count());
        }
    }

    public function test_freeze_throws_data_conflicted_when_frozen_changed_externally() {
        $wallet = $this->wallet(balance: 100, frozen: 0);

        try {
            DB::transaction(function () use ($wallet) {
                DB::table('base_wallet')->where('id', $wallet->id)->update(['frozen' => 50]);
                $wallet->freeze('hold', 10);
            });
            $this->fail('expected ServiceException was not thrown');
        } catch (ServiceException $e) {
            $this->assertSame('data-conflicted', $e->getError());
            $this->assertSame(0, FrozenLog::where('wallet_id', $wallet->id)->count());
        }
    }

    public function test_manipulate_succeeds_when_no_external_change() {
        $wallet = $this->wallet(balance: 100);

        DB::transaction(fn () => $wallet->manipulate('deposit', 10));

        $this->assertSame(110.0, (float) $wallet->fresh()->balance);
    }

    public function test_freeze_succeeds_when_no_external_change() {
        $wallet = $this->wallet(balance: 100, frozen: 0);

        DB::transaction(fn () => $wallet->freeze('hold', 25));

        $this->assertSame(25.0, (float) $wallet->fresh()->frozen);
    }

    public function test_manipulate_succeeds_on_freshly_created_wallet() {
        $member = $this->member();
        $this->currency('USD', 2);
        $wallet = Wallets::get($member, 'USD');

        DB::transaction(fn () => $wallet->manipulate('deposit', 10));

        $this->assertSame(10.0, (float) $wallet->fresh()->balance);
    }

}
