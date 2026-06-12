<?php //>

namespace Tests\Feature;

use Illuminate\Database\Eloquent\Collection;
use MatrixPlatform\Models\Currency;
use MatrixPlatform\Models\Wallet;
use MatrixPlatform\Support\Wallets;
use Tests\FeatureTestCase;

class HasWalletsTraitTest extends FeatureTestCase {

    public function test_wallet_method_returns_wallet_for_currency_code() {
        $member = $this->member();
        $this->currency('USD');

        $wallet = $member->wallet('USD');

        $this->assertInstanceOf(Wallet::class, $wallet);
        $this->assertSame('USD', $wallet->currency->code);
        $this->assertSame($member->id, $wallet->member_id);
    }

    public function test_wallets_attribute_resolves_via_list_helper() {
        $this->currency('USD', ranking: 100);
        $this->currency('TWD', ranking: 200);
        $member = $this->member();

        $wallets = $member->wallets;

        $this->assertInstanceOf(Collection::class, $wallets);
        $this->assertCount(2, $wallets);
        $this->assertSame('USD', $wallets->get(0)->currency->code);
        $this->assertSame('TWD', $wallets->get(1)->currency->code);
        $this->assertSame(2, Wallet::where('member_id', $member->id)->count());
    }

    public function test_wallets_attribute_is_cached_within_instance() {
        $this->currency('USD', ranking: 100);
        $member = $this->member();

        $first = $member->wallets;
        $second = $member->wallets;

        $this->assertSame($first, $second);
    }

    public function test_wallets_relation_returns_empty_when_no_wallets_persisted() {
        $this->currency('USD', ranking: 100);
        $member = $this->member();

        $this->assertCount(0, $member->wallets()->get());
    }

    public function test_wallets_relation_returns_only_persisted_wallets() {
        $this->currency('USD', ranking: 100);
        $this->currency('TWD', ranking: 200);
        $member = $this->member();

        Wallets::get($member, 'USD');

        $wallets = $member->wallets()->get();

        $this->assertCount(1, $wallets);
        $this->assertSame('USD', $wallets->first()->currency->code);
    }

    public function test_wallets_relation_includes_disabled_currency_wallets() {
        $disabled = Currency::forceCreate([
            'code' => 'OLD',
            'disable_time' => now()->subMinute(),
            'enable_time' => now()->subHour(),
            'precision' => 2,
            'title' => 'OLD',
        ]);
        $member = $this->member();

        Wallets::get($member, $disabled);

        $wallets = $member->wallets()->get();

        $this->assertCount(1, $wallets);
        $this->assertSame('OLD', $wallets->first()->currency->code);
    }

    public function test_wallets_relation_isolates_by_member() {
        $this->currency('USD', ranking: 100);
        $owner = $this->member();
        $other = $this->member();

        Wallets::get($owner, 'USD');

        $this->assertCount(1, $owner->wallets()->get());
        $this->assertCount(0, $other->wallets()->get());
    }

}
