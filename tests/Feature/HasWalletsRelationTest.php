<?php //>

namespace Tests\Feature;

use Illuminate\Database\Eloquent\Collection;
use MatrixPlatform\Models\Currency;
use MatrixPlatform\Models\Wallet;
use MatrixPlatform\Support\Wallets;
use Tests\WalletFeatureTestCase;

class HasWalletsRelationTest extends WalletFeatureTestCase {

    public function test_wallets_attribute_is_cached_within_instance() {
        $this->currency('USD', ranking: 100);
        $member = $this->member();

        $first = $member->wallets;
        $second = $member->wallets;

        $this->assertSame($first, $second);
    }

    public function test_wallets_attribute_resolves_via_list_helper() {
        $this->currency('USD', ranking: 100);
        $this->currency('TWD', ranking: 200);
        $member = $this->member();

        $wallets = $member->wallets;

        $this->assertCount(2, $wallets);
        $this->assertEquals('USD', $wallets->get(0)->currency->code);
        $this->assertEquals('TWD', $wallets->get(1)->currency->code);
        $this->assertEquals(2, Wallet::where('member_id', $member->id)->count());
    }

    public function test_wallets_relation_returns_empty_when_none_persisted() {
        $this->currency('USD', ranking: 100);
        $member = $this->member();

        $wallets = $member->wallets()->get();

        $this->assertCount(0, $wallets);
    }

    public function test_wallets_relation_returns_only_persisted_wallets() {
        $this->currency('USD', ranking: 100);
        $this->currency('TWD', ranking: 200);
        $member = $this->member();

        Wallets::get($member, 'USD');

        $wallets = $member->wallets()->get();

        $this->assertCount(1, $wallets);
        $this->assertEquals('USD', $wallets->first()->currency->code);
    }

    public function test_wallets_relation_includes_inactive_currency_wallets() {
        $disabled = Currency::forceCreate([
            'title' => 'OLD',
            'code' => 'OLD',
            'precision' => 2,
            'enable_time' => now()->subHour(),
            'disable_time' => now()->subMinute(),
        ]);
        $member = $this->member();

        Wallets::get($member, $disabled);

        $wallets = $member->wallets()->get();

        $this->assertCount(1, $wallets);
        $this->assertEquals('OLD', $wallets->first()->currency->code);
    }

    public function test_wallets_relation_isolates_by_member() {
        $this->currency('USD', ranking: 100);
        $owner = $this->member();
        $other = $this->member();

        Wallets::get($owner, 'USD');

        $this->assertCount(1, $owner->wallets()->get());
        $this->assertCount(0, $other->wallets()->get());
    }

    public function test_wallets_relation_returns_eloquent_collection() {
        $this->currency('USD', ranking: 100);
        $member = $this->member();

        Wallets::get($member, 'USD');

        $this->assertInstanceOf(Collection::class, $member->wallets()->get());
    }

}
