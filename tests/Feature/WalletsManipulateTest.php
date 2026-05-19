<?php //>

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use MatrixPlatform\Exceptions\ServiceException;
use MatrixPlatform\Models\WalletLog;
use Tests\WalletFeatureTestCase;

class WalletsManipulateTest extends WalletFeatureTestCase {

    // credit

    public function test_manipulate_credit_increases_balance() {
        $wallet = $this->wallet(balance: 100);

        DB::transaction(function () use ($wallet) {
            $wallet->manipulate('deposit', 50);
        });

        $this->assertEquals(150, $wallet->balance);
    }

    public function test_manipulate_repeated_credits_accumulate_balance() {
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

        $this->assertEquals(60, $wallet->balance);
    }

    // debit

    public function test_manipulate_debit_decreases_balance() {
        $wallet = $this->wallet(balance: 100);

        DB::transaction(function () use ($wallet) {
            $wallet->manipulate('withdraw', -30);
        });

        $this->assertEquals(70, $wallet->balance);
    }

    public function test_manipulate_debit_below_frozen_throws_insufficient_balance() {
        $wallet = $this->wallet(balance: 100, frozen: 60);

        try {
            DB::transaction(function () use ($wallet) {
                $wallet->manipulate('withdraw', -50);
                $this->fail('Expected ServiceException was not thrown');
            });
        } catch (ServiceException $e) {
            $this->assertEquals('insufficient-balance', $e->getError());
            $this->assertEquals(100, $wallet->balance);
        }
    }

    public function test_manipulate_debit_to_exactly_frozen_is_allowed() {
        $wallet = $this->wallet(balance: 100, frozen: 60);

        DB::transaction(function () use ($wallet) {
            $wallet->manipulate('withdraw', -40);
        });

        $this->assertEquals(60, $wallet->balance);
    }

    // precision

    public function test_manipulate_rounds_amount_to_currency_precision() {
        $wallet = $this->wallet(balance: 0, precision: 2);

        DB::transaction(function () use ($wallet) {
            $wallet->manipulate('deposit', 10.999);
        });

        $log = WalletLog::where('wallet_id', $wallet->id)->latest('id')->first();
        $this->assertEquals(11.00, (float) $log->amount);
        $this->assertEquals(11.00, (float) $wallet->balance);
    }

    public function test_manipulate_amount_zero_after_rounding_throws_error() {
        $wallet = $this->wallet(balance: 0, precision: 0);

        try {
            DB::transaction(function () use ($wallet) {
                $wallet->manipulate('deposit', 0.4);
                $this->fail('Expected ServiceException was not thrown');
            });
        } catch (ServiceException $e) {
            $this->assertEquals('invalid-manipulation-amount', $e->getError());
        }
    }

    // wallet log

    public function test_manipulate_creates_wallet_log_with_correct_balance() {
        $wallet = $this->wallet(balance: 100);

        DB::transaction(function () use ($wallet) {
            $wallet->manipulate('deposit', 50, 'test remark', ['key' => 'val']);
        });

        $log = WalletLog::where('wallet_id', $wallet->id)->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertEquals($wallet->id, $log->wallet_id);
        $this->assertEquals(50, (float) $log->amount);
        $this->assertEquals(150, (float) $log->balance);
        $this->assertEquals('deposit', $log->type);
        $this->assertEquals('test remark', $log->remark);
    }

    public function test_manipulate_logs_chain_correctly() {
        $wallet = $this->wallet(balance: 0);

        DB::transaction(function () use ($wallet) {
            $wallet->manipulate('deposit', 100);
        });
        DB::transaction(function () use ($wallet) {
            $wallet->manipulate('deposit', 50);
        });
        DB::transaction(function () use ($wallet) {
            $wallet->manipulate('withdraw', -30);
        });

        $logs = WalletLog::where('wallet_id', $wallet->id)->orderBy('id')->get();
        $this->assertCount(3, $logs);
        $this->assertEquals(100, (float) $logs[0]->balance);
        $this->assertEquals(150, (float) $logs[1]->balance);
        $this->assertEquals(120, (float) $logs[2]->balance);
    }

    public function test_manipulate_records_the_date() {
        $wallet = $this->wallet(balance: 0);

        DB::transaction(function () use ($wallet) {
            $wallet->manipulate('deposit', 10);
        });

        $log = WalletLog::where('wallet_id', $wallet->id)->latest('id')->first();
        $this->assertEquals(now()->toDateString(), $log->the_date);
    }

    // isolation

    public function test_manipulate_isolation_between_wallets() {
        $member = $this->member();
        $usd = $this->wallet(balance: 100, code: 'USD', member: $member);
        $twd = $this->wallet(balance: 200, code: 'TWD', member: $member);

        DB::transaction(function () use ($usd) {
            $usd->manipulate('deposit', 50);
        });

        $this->assertEquals(150, $usd->balance);
        $this->assertEquals(200, (float) $twd->fresh()->balance);
        $this->assertEquals(1, WalletLog::where('wallet_id', $usd->id)->count());
        $this->assertEquals(0, WalletLog::where('wallet_id', $twd->id)->count());
    }

}
