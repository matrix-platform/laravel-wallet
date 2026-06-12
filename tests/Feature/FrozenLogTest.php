<?php //>

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use MatrixPlatform\Models\FrozenLog;
use MatrixPlatform\Models\Wallet;
use Tests\FeatureTestCase;

class FrozenLogTest extends FeatureTestCase {

    public function test_amount_casts_to_float() {
        $wallet = $this->wallet(balance: 100);

        DB::transaction(fn () => $wallet->freeze('hold', 12.5));

        $log = FrozenLog::where('wallet_id', $wallet->id)->latest('id')->first();

        $this->assertIsFloat($log->amount);
        $this->assertSame(12.5, $log->amount);
    }

    public function test_data_cast_to_array() {
        $wallet = $this->wallet(balance: 100);

        DB::transaction(fn () => $wallet->freeze('hold', 10, null, ['ref' => 'r1']));

        $log = FrozenLog::where('wallet_id', $wallet->id)->latest('id')->first();

        $this->assertIsArray($log->data);
        $this->assertSame(['ref' => 'r1'], $log->data);
    }

    public function test_data_null_remains_null() {
        $wallet = $this->wallet(balance: 100);

        DB::transaction(fn () => $wallet->freeze('hold', 10));

        $log = FrozenLog::where('wallet_id', $wallet->id)->latest('id')->first();

        $this->assertNull($log->data);
    }

    public function test_wallet_relation_returns_owner_wallet() {
        $wallet = $this->wallet(balance: 100);

        DB::transaction(fn () => $wallet->freeze('hold', 30));

        $log = FrozenLog::where('wallet_id', $wallet->id)->first();

        $this->assertInstanceOf(Wallet::class, $log->wallet);
        $this->assertSame($wallet->id, $log->wallet->id);
    }

    public function test_table_has_create_time_but_no_update_time() {
        $this->assertTrue(Schema::hasColumn('base_frozen_log', 'create_time'));
        $this->assertFalse(Schema::hasColumn('base_frozen_log', 'update_time'));
    }

    public function test_create_time_is_populated_on_insert() {
        $wallet = $this->wallet(balance: 100);

        DB::transaction(fn () => $wallet->freeze('hold', 30));

        $row = DB::table('base_frozen_log')->where('wallet_id', $wallet->id)->first();

        $this->assertNotNull($row->create_time);
    }

    public function test_log_is_not_traced_in_manipulation_log() {
        $wallet = $this->wallet(balance: 100);

        DB::transaction(fn () => $wallet->freeze('hold', 30));

        $count = DB::table('base_manipulation_log')->where('data_type', 'base_frozen_log')->count();

        $this->assertSame(0, $count);
    }

    public function test_parent_returns_wallet() {
        $this->assertSame('wallet', (new FrozenLog)->getParent());
    }

}
