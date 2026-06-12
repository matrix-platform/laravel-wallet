<?php //>

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use MatrixPlatform\Exceptions\ServiceException;
use MatrixPlatform\Models\FrozenLog;
use Tests\FeatureTestCase;

class WalletsFreezeTest extends FeatureTestCase {

    public function test_positive_amount_increases_frozen() {
        $wallet = $this->wallet(balance: 100, frozen: 0);

        DB::transaction(fn () => $wallet->freeze('hold', 40));

        $this->assertSame(40.0, (float) $wallet->fresh()->frozen);
    }

    public function test_negative_amount_releases_frozen() {
        $wallet = $this->wallet(balance: 100, frozen: 60);

        DB::transaction(fn () => $wallet->freeze('release', -20));

        $this->assertSame(40.0, (float) $wallet->fresh()->frozen);
    }

    public function test_full_release_returns_frozen_to_zero() {
        $wallet = $this->wallet(balance: 100, frozen: 50);

        DB::transaction(fn () => $wallet->freeze('release', -50));

        $this->assertSame(0.0, (float) $wallet->fresh()->frozen);
    }

    public function test_freeze_exactly_at_balance_is_allowed() {
        $wallet = $this->wallet(balance: 100, frozen: 0);

        DB::transaction(fn () => $wallet->freeze('hold', 100));

        $this->assertSame(100.0, (float) $wallet->fresh()->frozen);
    }

    public function test_freeze_beyond_balance_throws_invalid_frozen_amount() {
        $wallet = $this->wallet(balance: 100, frozen: 0);

        try {
            DB::transaction(fn () => $wallet->freeze('hold', 101));
            $this->fail('expected ServiceException was not thrown');
        } catch (ServiceException $e) {
            $this->assertSame('invalid-frozen-amount', $e->getError());
            $this->assertSame(0.0, (float) $wallet->fresh()->frozen);
        }
    }

    public function test_release_below_zero_throws_invalid_frozen_amount() {
        $wallet = $this->wallet(balance: 100, frozen: 30);

        try {
            DB::transaction(fn () => $wallet->freeze('release', -31));
            $this->fail('expected ServiceException was not thrown');
        } catch (ServiceException $e) {
            $this->assertSame('invalid-frozen-amount', $e->getError());
            $this->assertSame(30.0, (float) $wallet->fresh()->frozen);
        }
    }

    public function test_zero_amount_throws_invalid_frozen_amount() {
        $wallet = $this->wallet(balance: 100);

        try {
            DB::transaction(fn () => $wallet->freeze('noop', 0));
            $this->fail('expected ServiceException was not thrown');
        } catch (ServiceException $e) {
            $this->assertSame('invalid-frozen-amount', $e->getError());
        }
    }

    public function test_amount_rounded_to_zero_throws_invalid_frozen_amount() {
        $wallet = $this->wallet(balance: 100, frozen: 0, precision: 0);

        try {
            DB::transaction(fn () => $wallet->freeze('hold', 0.4));
            $this->fail('expected ServiceException was not thrown');
        } catch (ServiceException $e) {
            $this->assertSame('invalid-frozen-amount', $e->getError());
        }
    }

    public function test_failed_freeze_rolls_back_state() {
        $wallet = $this->wallet(balance: 100, frozen: 0);

        try {
            DB::transaction(fn () => $wallet->freeze('hold', 200));
        } catch (ServiceException) {}

        $this->assertSame(0.0, (float) $wallet->fresh()->frozen);
        $this->assertSame(0, FrozenLog::where('wallet_id', $wallet->id)->count());
    }

    public function test_freeze_then_manipulate_to_frozen_boundary() {
        $wallet = $this->wallet(balance: 100, frozen: 0);

        DB::transaction(fn () => $wallet->freeze('hold', 60));
        DB::transaction(fn () => $wallet->manipulate('withdraw', -40));

        $fresh = $wallet->fresh();
        $this->assertSame(60.0, (float) $fresh->balance);
        $this->assertSame(60.0, (float) $fresh->frozen);
    }

    public function test_frozen_log_is_created_with_full_data() {
        $wallet = $this->wallet(balance: 100);

        DB::transaction(fn () => $wallet->freeze('hold', 50, 'remark', ['ref' => 'abc']));

        $log = FrozenLog::where('wallet_id', $wallet->id)->latest('id')->first();
        $this->assertSame($wallet->id, $log->wallet_id);
        $this->assertSame('hold', $log->type);
        $this->assertSame(50.0, (float) $log->amount);
        $this->assertSame('remark', $log->remark);
        $this->assertSame(['ref' => 'abc'], $log->data);
    }

    public function test_frozen_log_records_the_date_in_iso_format() {
        $wallet = $this->wallet(balance: 100);

        DB::transaction(fn () => $wallet->freeze('hold', 50));

        $log = FrozenLog::where('wallet_id', $wallet->id)->latest('id')->first();
        $this->assertSame(now()->toDateString(), $log->the_date);
    }

    public function test_freeze_returns_created_frozen_log() {
        $wallet = $this->wallet(balance: 100);

        $log = DB::transaction(fn () => $wallet->freeze('hold', 25));

        $this->assertInstanceOf(FrozenLog::class, $log);
        $this->assertSame(25.0, (float) $log->amount);
    }

}
