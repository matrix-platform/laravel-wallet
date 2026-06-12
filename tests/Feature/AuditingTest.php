<?php //>

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\FeatureTestCase;

class AuditingTest extends FeatureTestCase {

    public function test_wallet_has_create_time_and_update_time_columns() {
        $this->assertTrue(Schema::hasColumn('base_wallet', 'create_time'));
        $this->assertTrue(Schema::hasColumn('base_wallet', 'update_time'));
    }

    public function test_currency_has_create_time_and_update_time_columns() {
        $this->assertTrue(Schema::hasColumn('base_currency', 'create_time'));
        $this->assertTrue(Schema::hasColumn('base_currency', 'update_time'));
    }

    public function test_wallet_persists_create_time_on_insert() {
        $wallet = $this->wallet();

        $row = DB::table('base_wallet')->where('id', $wallet->id)->first();

        $this->assertNotNull($row->create_time);
    }

    public function test_wallet_starts_with_null_update_time() {
        $member = $this->member();
        $this->currency('USD');
        $wallet = \MatrixPlatform\Support\Wallets::get($member, 'USD');

        $row = DB::table('base_wallet')->where('id', $wallet->id)->first();
        $this->assertNull($row->update_time);
    }

    public function test_wallet_update_time_is_set_on_manipulate() {
        $wallet = $this->wallet();

        DB::transaction(fn () => $wallet->manipulate('deposit', 10));

        $row = DB::table('base_wallet')->where('id', $wallet->id)->first();
        $this->assertNotNull($row->update_time);
    }

    public function test_currency_update_time_is_set_on_save() {
        $currency = $this->currency('USD');

        $beforeUpdate = DB::table('base_currency')->where('id', $currency->id)->value('update_time');
        $this->assertNull($beforeUpdate);

        $currency->title = 'US Dollar';
        $currency->save();

        $afterUpdate = DB::table('base_currency')->where('id', $currency->id)->value('update_time');
        $this->assertNotNull($afterUpdate);
    }

    public function test_wallet_is_not_written_to_manipulation_log() {
        $wallet = $this->wallet(balance: 100);

        DB::transaction(fn () => $wallet->manipulate('deposit', 10));

        $this->assertSame(0, DB::table('base_manipulation_log')->where('data_type', 'base_wallet')->count());
    }

    public function test_wallet_log_is_not_written_to_manipulation_log() {
        $wallet = $this->wallet(balance: 100);

        DB::transaction(fn () => $wallet->manipulate('deposit', 10));

        $this->assertSame(0, DB::table('base_manipulation_log')->where('data_type', 'base_wallet_log')->count());
    }

    public function test_frozen_log_is_not_written_to_manipulation_log() {
        $wallet = $this->wallet(balance: 100);

        DB::transaction(fn () => $wallet->freeze('hold', 20));

        $this->assertSame(0, DB::table('base_manipulation_log')->where('data_type', 'base_frozen_log')->count());
    }

    public function test_currency_is_written_to_manipulation_log() {
        $currency = $this->currency('USD');

        $log = DB::table('base_manipulation_log')->where('data_type', 'base_currency')->where('data_id', $currency->id)->first();

        $this->assertNotNull($log);
        $this->assertSame(1, $log->type);
    }

}
