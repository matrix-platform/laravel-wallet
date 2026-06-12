<?php //>

namespace Tests\Feature;

use MatrixPlatform\Models\Currency;
use MatrixPlatform\Models\Wallet;
use MatrixPlatform\Support\Wallets;
use Tests\FeatureTestCase;

class WalletsListTest extends FeatureTestCase {

    public function test_list_returns_one_wallet_per_active_currency() {
        $this->currency('USD', ranking: 100);
        $this->currency('TWD', ranking: 200);
        $member = $this->member();

        $wallets = Wallets::list($member);

        $this->assertCount(2, $wallets);
        $this->assertSame(['USD', 'TWD'], $wallets->pluck('currency.code')->all());
    }

    public function test_list_excludes_currencies_with_passed_disable_time() {
        $this->currency('USD', ranking: 100);
        Currency::forceCreate([
            'code' => 'OLD',
            'disable_time' => now()->subMinute(),
            'enable_time' => now()->subHour(),
            'precision' => 2,
            'title' => 'OLD',
        ]);
        $member = $this->member();

        $wallets = Wallets::list($member);

        $this->assertCount(1, $wallets);
        $this->assertSame('USD', $wallets->first()->currency->code);
    }

    public function test_list_excludes_currencies_with_future_enable_time() {
        $this->currency('USD', ranking: 100);
        Currency::forceCreate([
            'code' => 'FUT',
            'enable_time' => now()->addHour(),
            'precision' => 2,
            'title' => 'FUT',
        ]);
        $member = $this->member();

        $wallets = Wallets::list($member);

        $this->assertCount(1, $wallets);
        $this->assertSame('USD', $wallets->first()->currency->code);
    }

    public function test_list_orders_by_currency_ranking() {
        $this->currency('TWD', ranking: 200);
        $this->currency('USD', ranking: 100);
        $member = $this->member();

        $wallets = Wallets::list($member);

        $this->assertSame('USD', $wallets->get(0)->currency->code);
        $this->assertSame('TWD', $wallets->get(1)->currency->code);
    }

    public function test_list_is_keyed_by_position() {
        $this->currency('USD', ranking: 100);
        $this->currency('TWD', ranking: 200);
        $this->currency('JPY', ranking: 300);
        $member = $this->member();

        $wallets = Wallets::list($member);

        $this->assertSame([0, 1, 2], $wallets->keys()->all());
    }

    public function test_list_persists_auto_created_wallets() {
        $this->currency('USD', ranking: 100);
        $this->currency('TWD', ranking: 200);
        $member = $this->member();

        Wallets::list($member);

        $this->assertSame(2, Wallet::where('member_id', $member->id)->count());
    }

    public function test_list_preserves_existing_wallet_data() {
        $this->currency('USD', ranking: 100);
        $this->currency('TWD', ranking: 200);
        $member = $this->member();

        $usd = Wallets::get($member, 'USD');
        $usd->balance = 500;
        $usd->save();

        $wallets = Wallets::list($member);

        $existing = $wallets->first(fn ($w) => $w->currency->code === 'USD');
        $this->assertSame(500.0, (float) $existing->balance);
        $this->assertSame(2, Wallet::where('member_id', $member->id)->count());
    }

    public function test_list_sets_currency_and_member_relations() {
        $this->currency('USD', ranking: 100);
        $member = $this->member();

        $wallet = Wallets::list($member)->first();

        $this->assertTrue($wallet->relationLoaded('currency'));
        $this->assertTrue($wallet->relationLoaded('member'));
        $this->assertSame('USD', $wallet->currency->code);
        $this->assertSame($member->id, $wallet->member->id);
    }

    public function test_list_returns_empty_when_no_active_currencies_exist() {
        $member = $this->member();

        $wallets = Wallets::list($member);

        $this->assertCount(0, $wallets);
    }

}
