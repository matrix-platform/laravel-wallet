<?php //>

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use MatrixPlatform\Exceptions\ServiceException;
use MatrixPlatform\Models\WalletLog;
use Tests\FeatureTestCase;

class WalletsManipulateTest extends FeatureTestCase {

    public function test_positive_amount_credits_balance() {
        $wallet = $this->wallet(balance: 100);

        DB::transaction(fn () => $wallet->manipulate('deposit', 50));

        $this->assertSame(150.0, (float) $wallet->balance);
        $this->assertSame(150.0, (float) $wallet->fresh()->balance);
    }

    public function test_negative_amount_debits_balance() {
        $wallet = $this->wallet(balance: 100);

        DB::transaction(fn () => $wallet->manipulate('withdraw', -30));

        $this->assertSame(70.0, (float) $wallet->balance);
        $this->assertSame(70.0, (float) $wallet->fresh()->balance);
    }

    public function test_repeated_manipulations_accumulate_balance() {
        $wallet = $this->wallet(balance: 0);

        DB::transaction(fn () => $wallet->manipulate('deposit', 10));
        DB::transaction(fn () => $wallet->manipulate('deposit', 20));
        DB::transaction(fn () => $wallet->manipulate('deposit', 30));

        $this->assertSame(60.0, (float) $wallet->fresh()->balance);
    }

    public function test_debit_below_frozen_throws_insufficient_balance() {
        $wallet = $this->wallet(balance: 100, frozen: 60);

        try {
            DB::transaction(fn () => $wallet->manipulate('withdraw', -50));
            $this->fail('expected ServiceException was not thrown');
        } catch (ServiceException $e) {
            $this->assertSame('insufficient-balance', $e->getError());
            $this->assertSame(100.0, (float) $wallet->fresh()->balance);
        }
    }

    public function test_debit_exactly_to_frozen_is_allowed() {
        $wallet = $this->wallet(balance: 100, frozen: 60);

        DB::transaction(fn () => $wallet->manipulate('withdraw', -40));

        $this->assertSame(60.0, (float) $wallet->fresh()->balance);
    }

    public function test_zero_amount_throws_invalid_manipulation_amount() {
        $wallet = $this->wallet(balance: 100);

        try {
            DB::transaction(fn () => $wallet->manipulate('noop', 0));
            $this->fail('expected ServiceException was not thrown');
        } catch (ServiceException $e) {
            $this->assertSame('invalid-manipulation-amount', $e->getError());
        }
    }

    public function test_amount_rounded_to_zero_throws_invalid_manipulation_amount() {
        $wallet = $this->wallet(balance: 100, precision: 0);

        try {
            DB::transaction(fn () => $wallet->manipulate('deposit', 0.4));
            $this->fail('expected ServiceException was not thrown');
        } catch (ServiceException $e) {
            $this->assertSame('invalid-manipulation-amount', $e->getError());
        }
    }

    public function test_amount_is_rounded_to_currency_precision() {
        $wallet = $this->wallet(balance: 0, precision: 2);

        DB::transaction(fn () => $wallet->manipulate('deposit', 10.999));

        $log = WalletLog::where('wallet_id', $wallet->id)->latest('id')->first();
        $this->assertSame(11.0, (float) $log->amount);
        $this->assertSame(11.0, (float) $wallet->fresh()->balance);
    }

    public function test_failed_manipulation_rolls_back_balance() {
        $wallet = $this->wallet(balance: 100);

        try {
            DB::transaction(fn () => $wallet->manipulate('withdraw', -200));
        } catch (ServiceException) {}

        $this->assertSame(100.0, (float) $wallet->fresh()->balance);
        $this->assertSame(0, WalletLog::where('wallet_id', $wallet->id)->count());
    }

    public function test_wallet_log_is_created_with_full_data() {
        $wallet = $this->wallet(balance: 100);

        DB::transaction(fn () => $wallet->manipulate('deposit', 50, 'remark', ['k' => 'v']));

        $log = WalletLog::where('wallet_id', $wallet->id)->latest('id')->first();
        $this->assertSame($wallet->id, $log->wallet_id);
        $this->assertSame('deposit', $log->type);
        $this->assertSame(50.0, (float) $log->amount);
        $this->assertSame(150.0, (float) $log->balance);
        $this->assertSame('remark', $log->remark);
        $this->assertSame(['k' => 'v'], $log->data);
    }

    public function test_wallet_log_records_post_change_balance_snapshot() {
        $wallet = $this->wallet(balance: 0);

        DB::transaction(fn () => $wallet->manipulate('deposit', 100));
        DB::transaction(fn () => $wallet->manipulate('deposit', 50));
        DB::transaction(fn () => $wallet->manipulate('withdraw', -30));

        $logs = WalletLog::where('wallet_id', $wallet->id)->orderBy('id')->get();
        $this->assertCount(3, $logs);
        $this->assertSame(100.0, (float) $logs[0]->balance);
        $this->assertSame(150.0, (float) $logs[1]->balance);
        $this->assertSame(120.0, (float) $logs[2]->balance);
    }

    public function test_wallet_log_records_the_date_in_iso_format() {
        $wallet = $this->wallet(balance: 0);

        DB::transaction(fn () => $wallet->manipulate('deposit', 10));

        $log = WalletLog::where('wallet_id', $wallet->id)->latest('id')->first();
        $this->assertSame(now()->toDateString(), $log->the_date);
    }

    public function test_manipulate_returns_created_wallet_log() {
        $wallet = $this->wallet(balance: 0);

        $log = DB::transaction(fn () => $wallet->manipulate('deposit', 25));

        $this->assertInstanceOf(WalletLog::class, $log);
        $this->assertSame(25.0, (float) $log->amount);
    }

    public function test_manipulate_isolates_state_between_wallets() {
        $member = $this->member();
        $usd = $this->wallet(balance: 100, code: 'USD', member: $member);
        $twd = $this->wallet(balance: 200, code: 'TWD', member: $member);

        DB::transaction(fn () => $usd->manipulate('deposit', 50));

        $this->assertSame(150.0, (float) $usd->fresh()->balance);
        $this->assertSame(200.0, (float) $twd->fresh()->balance);
        $this->assertSame(1, WalletLog::where('wallet_id', $usd->id)->count());
        $this->assertSame(0, WalletLog::where('wallet_id', $twd->id)->count());
    }

}
