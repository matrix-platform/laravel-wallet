<?php //>

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use MatrixPlatform\Exceptions\ServiceException;
use MatrixPlatform\Models\FrozenLog;
use Tests\WalletFeatureTestCase;

class WalletsFreezeTest extends WalletFeatureTestCase {

    // freeze

    public function test_freeze_increases_frozen_amount() {
        $wallet = $this->wallet(balance: 100, frozen: 0);

        DB::transaction(function () use ($wallet) {
            $wallet->freeze('order', 40);
        });

        $this->assertEquals(40, $wallet->frozen);
    }

    public function test_freeze_negative_amount_decreases_frozen() {
        $wallet = $this->wallet(balance: 100, frozen: 60);

        DB::transaction(function () use ($wallet) {
            $wallet->freeze('release', -20);
        });

        $this->assertEquals(40, $wallet->frozen);
    }

    public function test_freeze_to_zero_after_full_release_is_allowed() {
        $wallet = $this->wallet(balance: 100, frozen: 0);

        DB::transaction(function () use ($wallet) {
            $wallet->freeze('order', 50);
        });
        DB::transaction(function () use ($wallet) {
            $wallet->freeze('release', -50);
        });

        $this->assertEquals(0, $wallet->frozen);
    }

    // bounds

    public function test_freeze_beyond_balance_throws_invalid_frozen_amount() {
        $wallet = $this->wallet(balance: 100, frozen: 0);

        try {
            DB::transaction(function () use ($wallet) {
                $wallet->freeze('order', 101);
                $this->fail('Expected ServiceException was not thrown');
            });
        } catch (ServiceException $e) {
            $this->assertEquals('invalid-frozen-amount', $e->getError());
            $this->assertEquals(0, $wallet->frozen);
        }
    }

    public function test_freeze_to_exactly_balance_is_allowed() {
        $wallet = $this->wallet(balance: 100, frozen: 0);

        DB::transaction(function () use ($wallet) {
            $wallet->freeze('order', 100);
        });

        $this->assertEquals(100, $wallet->frozen);
    }

    public function test_freeze_unfreeze_beyond_current_frozen_throws_error() {
        $wallet = $this->wallet(balance: 100, frozen: 30);

        try {
            DB::transaction(function () use ($wallet) {
                $wallet->freeze('release', -31);
                $this->fail('Expected ServiceException was not thrown');
            });
        } catch (ServiceException $e) {
            $this->assertEquals('invalid-frozen-amount', $e->getError());
            $this->assertEquals(30, $wallet->frozen);
        }
    }

    // interaction

    public function test_freeze_then_manipulate_debit_to_frozen_boundary() {
        $wallet = $this->wallet(balance: 100, frozen: 0);

        DB::transaction(function () use ($wallet) {
            $wallet->freeze('order', 60);
        });
        DB::transaction(function () use ($wallet) {
            $wallet->manipulate('withdraw', -40);
        });

        $this->assertEquals(60, (float) $wallet->balance);
        $this->assertEquals(60, (float) $wallet->frozen);
    }

    // precision

    public function test_freeze_amount_zero_after_rounding_throws_error() {
        $wallet = $this->wallet(balance: 100, frozen: 0, precision: 0);

        try {
            DB::transaction(function () use ($wallet) {
                $wallet->freeze('order', 0.4);
                $this->fail('Expected ServiceException was not thrown');
            });
        } catch (ServiceException $e) {
            $this->assertEquals('invalid-frozen-amount', $e->getError());
        }
    }

    // frozen log

    public function test_freeze_creates_frozen_log_with_correct_data() {
        $wallet = $this->wallet(balance: 100, frozen: 0);

        DB::transaction(function () use ($wallet) {
            $wallet->freeze('order', 50, 'hold', ['ref' => 'abc']);
        });

        $log = FrozenLog::where('wallet_id', $wallet->id)->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertEquals($wallet->id, $log->wallet_id);
        $this->assertEquals(50, (float) $log->amount);
        $this->assertEquals('order', $log->type);
        $this->assertEquals('hold', $log->remark);
        $this->assertEquals(['ref' => 'abc'], $log->data);
    }

    public function test_freeze_records_the_date() {
        $wallet = $this->wallet(balance: 100);

        DB::transaction(function () use ($wallet) {
            $wallet->freeze('order', 50);
        });

        $log = FrozenLog::where('wallet_id', $wallet->id)->latest('id')->first();
        $this->assertEquals(now()->toDateString(), $log->the_date);
    }

}
