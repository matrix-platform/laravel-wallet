<?php //>

namespace Tests\Feature;

use Carbon\Carbon;
use MatrixPlatform\Models\Currency;
use Tests\FeatureTestCase;

class CurrencyTest extends FeatureTestCase {

    public function test_round_uses_precision_zero() {
        $currency = $this->currency('JPY', precision: 0);

        $this->assertSame(11.0, $currency->round(10.5));
        $this->assertSame(10.0, $currency->round(10.4));
        $this->assertSame(-11.0, $currency->round(-10.5));
    }

    public function test_round_uses_precision_two() {
        $currency = $this->currency('USD', precision: 2);

        $this->assertSame(10.99, $currency->round(10.99));
        $this->assertSame(11.0, $currency->round(10.999));
        $this->assertSame(10.99, $currency->round(10.994));
    }

    public function test_round_uses_precision_eight() {
        $currency = $this->currency('BTC', precision: 8);

        $this->assertSame(0.12345678, $currency->round(0.123456784));
        $this->assertSame(0.12345679, $currency->round(0.123456785));
    }

    public function test_enable_and_disable_time_cast_to_carbon() {
        $enable = now()->subHour()->startOfSecond();
        $disable = now()->addHour()->startOfSecond();
        $created = $this->currency('USD', enable: $enable, disable: $disable);

        $fresh = Currency::find($created->id);

        $this->assertInstanceOf(Carbon::class, $fresh->enable_time);
        $this->assertInstanceOf(Carbon::class, $fresh->disable_time);
        $this->assertEqualsWithDelta($enable->timestamp, $fresh->enable_time->timestamp, 1);
        $this->assertEqualsWithDelta($disable->timestamp, $fresh->disable_time->timestamp, 1);
    }

    public function test_to_title_returns_title_column() {
        $currency = $this->currency('USD');
        $currency->title = 'United States Dollar';
        $currency->save();

        $this->assertSame('United States Dollar', $currency->fresh()->toTitle());
    }

    public function test_where_active_includes_currency_within_window() {
        $currency = $this->currency('USD', enable: now()->subHour(), disable: now()->addHour());

        $matched = Currency::whereActive()->where('id', $currency->id)->exists();

        $this->assertTrue($matched);
    }

    public function test_where_active_excludes_future_enable_time() {
        $currency = Currency::forceCreate([
            'code' => 'FUT',
            'enable_time' => now()->addHour(),
            'precision' => 2,
            'title' => 'FUT',
        ]);

        $matched = Currency::whereActive()->where('id', $currency->id)->exists();

        $this->assertFalse($matched);
    }

    public function test_where_active_excludes_passed_disable_time() {
        $currency = Currency::forceCreate([
            'code' => 'OLD',
            'disable_time' => now()->subMinute(),
            'enable_time' => now()->subHour(),
            'precision' => 2,
            'title' => 'OLD',
        ]);

        $matched = Currency::whereActive()->where('id', $currency->id)->exists();

        $this->assertFalse($matched);
    }

    public function test_where_active_treats_null_disable_time_as_always_on() {
        $currency = Currency::forceCreate([
            'code' => 'EVR',
            'disable_time' => null,
            'enable_time' => now()->subHour(),
            'precision' => 2,
            'title' => 'EVR',
        ]);

        $matched = Currency::whereActive()->where('id', $currency->id)->exists();

        $this->assertTrue($matched);
    }

    public function test_ranking_auto_increments_when_not_specified() {
        $first = $this->currency('USD');
        $second = $this->currency('TWD');

        $this->assertGreaterThan($first->ranking, $second->fresh()->ranking);
    }

}
