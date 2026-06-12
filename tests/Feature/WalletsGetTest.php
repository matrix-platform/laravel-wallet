<?php //>

namespace Tests\Feature;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use MatrixPlatform\Models\Currency;
use MatrixPlatform\Models\Wallet;
use MatrixPlatform\Support\Wallets;
use Tests\FeatureTestCase;

class WalletsGetTest extends FeatureTestCase {

    public function test_get_auto_creates_wallet_when_absent() {
        $member = $this->member();
        $this->currency('USD');

        $wallet = Wallets::get($member, 'USD');

        $this->assertNotNull($wallet->id);
        $this->assertSame(0.0, (float) $wallet->balance);
        $this->assertSame(0.0, (float) $wallet->frozen);
        $this->assertSame(1, Wallet::where('member_id', $member->id)->count());
    }

    public function test_get_does_not_duplicate_existing_wallet() {
        $member = $this->member();
        $this->currency('USD');

        $first = Wallets::get($member, 'USD');
        $second = Wallets::get($member, 'USD');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Wallet::where('member_id', $member->id)->count());
    }

    public function test_get_persists_auto_created_wallet_to_database() {
        $member = $this->member();
        $this->currency('USD');

        $wallet = Wallets::get($member, 'USD');

        $this->assertNotNull(Wallet::find($wallet->id));
    }

    public function test_get_accepts_currency_code_string() {
        $member = $this->member();
        $this->currency('USD');

        $wallet = Wallets::get($member, 'USD');

        $this->assertSame('USD', $wallet->currency->code);
    }

    public function test_get_accepts_currency_object() {
        $member = $this->member();
        $currency = $this->currency('USD');

        $wallet = Wallets::get($member, $currency);

        $this->assertSame($currency->id, $wallet->currency_id);
    }

    public function test_get_with_currency_object_skips_active_lookup() {
        $member = $this->member();
        $currency = Currency::forceCreate([
            'code' => 'FUT',
            'enable_time' => now()->addHour(),
            'precision' => 2,
            'title' => 'FUT',
        ]);

        $wallet = Wallets::get($member, $currency);

        $this->assertSame($currency->id, $wallet->currency_id);
    }

    public function test_get_throws_when_currency_code_not_found() {
        $member = $this->member();

        $this->expectException(ModelNotFoundException::class);

        Wallets::get($member, 'INVALID');
    }

    public function test_get_sets_currency_relation() {
        $member = $this->member();
        $this->currency('USD');

        $wallet = Wallets::get($member, 'USD');

        $this->assertTrue($wallet->relationLoaded('currency'));
        $this->assertSame('USD', $wallet->currency->code);
    }

    public function test_get_sets_member_relation() {
        $member = $this->member();
        $this->currency('USD');

        $wallet = Wallets::get($member, 'USD');

        $this->assertTrue($wallet->relationLoaded('member'));
        $this->assertSame($member->id, $wallet->member->id);
    }

    public function test_get_isolates_wallets_by_member() {
        $this->currency('USD');
        $owner = $this->member();
        $other = $this->member();

        Wallets::get($owner, 'USD');

        $this->assertSame(1, Wallet::where('member_id', $owner->id)->count());
        $this->assertSame(0, Wallet::where('member_id', $other->id)->count());
    }

}
