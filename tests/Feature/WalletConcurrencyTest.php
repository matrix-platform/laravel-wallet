<?php //>

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use MatrixPlatform\Exceptions\ServiceException;
use Tests\WalletFeatureTestCase;

class WalletConcurrencyTest extends WalletFeatureTestCase {

    public function test_manipulate_throws_data_conflicted_when_balance_changed_externally() {
        $wallet = $this->wallet(balance: 100);

        try {
            DB::transaction(function () use ($wallet) {
                DB::table('base_wallet')->where('id', $wallet->id)->update(['balance' => 999]);
                $wallet->manipulate('deposit', 10);
                $this->fail('Expected ServiceException was not thrown');
            });
        } catch (ServiceException $e) {
            $this->assertEquals('data-conflicted', $e->getError());
        }
    }

    public function test_freeze_throws_data_conflicted_when_frozen_changed_externally() {
        $wallet = $this->wallet(balance: 100, frozen: 0);

        try {
            DB::transaction(function () use ($wallet) {
                DB::table('base_wallet')->where('id', $wallet->id)->update(['frozen' => 50]);
                $wallet->freeze('order', 10);
                $this->fail('Expected ServiceException was not thrown');
            });
        } catch (ServiceException $e) {
            $this->assertEquals('data-conflicted', $e->getError());
        }
    }

    public function test_manipulate_succeeds_when_no_external_change() {
        $wallet = $this->wallet(balance: 100);

        DB::transaction(function () use ($wallet) {
            $wallet->manipulate('deposit', 10);
        });

        $this->assertEquals(110, (float) $wallet->fresh()->balance);
    }

}
