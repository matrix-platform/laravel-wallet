<?php //>

namespace Tests\Feature;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use MatrixPlatform\Models\Currency;
use MatrixPlatform\Models\Wallet;
use MatrixPlatform\Support\Wallets;
use Tests\WalletFeatureTestCase;

class WalletsGetTest extends WalletFeatureTestCase {

    // auto-create

    public function test_get_auto_creates_wallet_if_not_exists() {
        $member = $this->member();
        $this->currency('USD');

        $wallet = $member->wallet('USD');

        $this->assertNotNull($wallet->id);
        $this->assertEquals(0, (float) $wallet->balance);
        $this->assertEquals(0, (float) $wallet->frozen);
        $this->assertEquals(1, Wallet::where('member_id', $member->id)->count());
    }

    public function test_get_does_not_duplicate_wallet_on_repeated_calls() {
        $member = $this->member();
        $this->currency('USD');

        $first = $member->wallet('USD');
        $second = $member->wallet('USD');

        $this->assertEquals($first->id, $second->id);
        $this->assertEquals(1, Wallet::where('member_id', $member->id)->count());
    }

    public function test_get_persists_wallet_to_database() {
        $member = $this->member();
        $this->currency('USD');

        $wallet = $member->wallet('USD');

        $this->assertNotNull(Wallet::find($wallet->id));
    }

    // currency code string

    public function test_get_accepts_currency_code_string() {
        $member = $this->member();
        $this->currency('USD');

        $wallet = $member->wallet('USD');

        $this->assertEquals('USD', $wallet->currency->code);
    }

    // not found

    public function test_get_throws_model_not_found_for_invalid_currency() {
        $member = $this->member();

        $this->expectException(ModelNotFoundException::class);

        $member->wallet('INVALID');
    }

    // currency object

    public function test_get_accepts_currency_object() {
        $member = $this->member();
        $currency = $this->currency('USD');

        $wallet = $member->wallet($currency);

        $this->assertEquals('USD', $wallet->currency->code);
    }

    public function test_get_with_currency_object_skips_active_filter() {
        $member = $this->member();
        $currency = Currency::forceCreate([
            'title' => 'EUR',
            'code' => 'EUR',
            'precision' => 2,
            'enable_time' => now()->addHour(),
        ]);

        $wallet = Wallets::get($member, $currency);

        $this->assertEquals($currency->id, $wallet->currency_id);
    }

    // relation

    public function test_get_returns_wallet_with_currency_relation_set() {
        $member = $this->member();
        $this->currency('USD');

        $wallet = $member->wallet('USD');

        $this->assertTrue($wallet->relationLoaded('currency'));
        $this->assertEquals('USD', $wallet->currency->code);
    }

    public function test_get_returns_wallet_with_member_relation_set() {
        $member = $this->member();
        $this->currency('USD');

        $wallet = $member->wallet('USD');

        $this->assertTrue($wallet->relationLoaded('member'));
        $this->assertEquals($member->id, $wallet->member->id);
    }

}
