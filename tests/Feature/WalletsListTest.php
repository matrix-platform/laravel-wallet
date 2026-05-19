<?php //>

namespace Tests\Feature;

use MatrixPlatform\Models\Currency;
use MatrixPlatform\Models\Wallet;
use MatrixPlatform\Support\Wallets;
use Tests\WalletFeatureTestCase;

class WalletsListTest extends WalletFeatureTestCase {

    // active filtering

    public function test_list_excludes_inactive_currencies() {
        $this->currency('USD', ranking: 100);
        Currency::forceCreate([
            'title' => 'TWD',
            'code' => 'TWD',
            'precision' => 0,
            'enable_time' => now()->subHour(),
            'disable_time' => now()->subMinute(),
        ]);
        $member = $this->member();

        $wallets = Wallets::list($member);

        $this->assertCount(1, $wallets);
        $this->assertEquals('USD', $wallets->first()->currency->code);
    }

    public function test_list_excludes_future_enable_time_currencies() {
        $this->currency('USD', ranking: 100);
        Currency::forceCreate([
            'title' => 'BTC',
            'code' => 'BTC',
            'precision' => 8,
            'enable_time' => now()->addHour(),
        ]);
        $member = $this->member();

        $wallets = Wallets::list($member);

        $this->assertCount(1, $wallets);
        $this->assertEquals('USD', $wallets->first()->currency->code);
    }

    // ordering

    public function test_list_orders_wallets_by_currency_ranking() {
        $this->currency('TWD', ranking: 200);
        $this->currency('USD', ranking: 100);
        $member = $this->member();

        $wallets = Wallets::list($member);

        $this->assertEquals('USD', $wallets->get(0)->currency->code);
        $this->assertEquals('TWD', $wallets->get(1)->currency->code);
    }

    public function test_list_returns_collection_keyed_by_position() {
        $this->currency('USD', ranking: 100);
        $this->currency('TWD', ranking: 200);
        $this->currency('JPY', ranking: 300);
        $member = $this->member();

        $wallets = Wallets::list($member);

        $this->assertEquals([0, 1, 2], $wallets->keys()->all());
    }

    // auto-create

    public function test_list_auto_creates_wallets_for_all_active_currencies() {
        $this->currency('USD', ranking: 100);
        $this->currency('TWD', ranking: 200);
        $member = $this->member();

        $wallets = Wallets::list($member);

        $this->assertCount(2, $wallets);
        $this->assertEquals(2, Wallet::where('member_id', $member->id)->count());
    }

    public function test_list_preserves_existing_wallet_data() {
        $this->currency('USD', ranking: 100);
        $this->currency('TWD', ranking: 200);
        $member = $this->member();

        $usdWallet = $member->wallet('USD');
        $usdWallet->balance = 500;
        $usdWallet->save();

        $wallets = Wallets::list($member);

        $usd = $wallets->first(fn ($w) => $w->currency->code === 'USD');
        $this->assertEquals(500, (float) $usd->balance);
        $this->assertEquals(2, Wallet::where('member_id', $member->id)->count());
    }

    // relations

    public function test_list_sets_currency_and_member_relations() {
        $this->currency('USD', ranking: 100);
        $member = $this->member();

        $wallets = Wallets::list($member);

        $wallet = $wallets->first();
        $this->assertTrue($wallet->relationLoaded('currency'));
        $this->assertTrue($wallet->relationLoaded('member'));
        $this->assertEquals($member->id, $wallet->member->id);
        $this->assertEquals('USD', $wallet->currency->code);
    }

}
