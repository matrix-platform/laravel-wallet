<?php //>

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\WalletFeatureTestCase;

class AuditingTest extends WalletFeatureTestCase {

    public function test_wallet_persists_create_time_on_insert() {
        $wallet = $this->wallet();

        $row = DB::table('base_wallet')->where('id', $wallet->id)->first();

        $this->assertNotNull($row->create_time);
    }

    public function test_wallet_persists_update_time_on_manipulate() {
        $wallet = $this->wallet();
        $before = DB::table('base_wallet')->where('id', $wallet->id)->value('update_time');

        DB::transaction(function () use ($wallet) {
            $wallet->manipulate('deposit', 10);
        });

        $after = DB::table('base_wallet')->where('id', $wallet->id)->value('update_time');
        $this->assertNull($before);
        $this->assertNotNull($after);
    }

    public function test_currency_persists_audit_columns() {
        $currency = $this->currency('USD');

        $beforeRow = DB::table('base_currency')->where('id', $currency->id)->first();
        $this->assertNotNull($beforeRow->create_time);
        $this->assertNull($beforeRow->update_time);

        $currency->title = 'US Dollar';
        $currency->save();

        $afterRow = DB::table('base_currency')->where('id', $currency->id)->first();
        $this->assertNotNull($afterRow->update_time);
    }

    public function test_wallet_log_has_no_update_time_column() {
        $this->assertTrue(Schema::hasColumn('base_wallet_log', 'create_time'));
        $this->assertFalse(Schema::hasColumn('base_wallet_log', 'update_time'));
    }

    public function test_frozen_log_has_no_update_time_column() {
        $this->assertTrue(Schema::hasColumn('base_frozen_log', 'create_time'));
        $this->assertFalse(Schema::hasColumn('base_frozen_log', 'update_time'));
    }

    public function test_manipulation_log_is_not_written_for_wallet_changes() {
        $wallet = $this->wallet(balance: 100);

        DB::transaction(function () use ($wallet) {
            $wallet->manipulate('deposit', 10);
        });

        $count = DB::table('base_manipulation_log')->where('data_type', 'base_wallet')->count();
        $this->assertEquals(0, $count);
    }

    public function test_manipulation_log_is_not_written_for_log_inserts() {
        $wallet = $this->wallet(balance: 100);

        DB::transaction(function () use ($wallet) {
            $wallet->manipulate('deposit', 10);
            $wallet->freeze('order', 20);
        });

        $walletLogCount = DB::table('base_manipulation_log')->where('data_type', 'base_wallet_log')->count();
        $frozenLogCount = DB::table('base_manipulation_log')->where('data_type', 'base_frozen_log')->count();
        $this->assertEquals(0, $walletLogCount);
        $this->assertEquals(0, $frozenLogCount);
    }

}
