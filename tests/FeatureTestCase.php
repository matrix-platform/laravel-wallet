<?php //>

namespace Tests;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use MatrixPlatform\Models\Currency;
use MatrixPlatform\Models\Wallet;
use MatrixPlatform\Support\Wallets;
use Tests\Models\TestMember;

class FeatureTestCase extends TestCase {

    use RefreshDatabase;

    protected function defineEnvironment($app) {
        $app['config']->set('app.key', 'base64:' . base64_encode(str_repeat('a', 32)));
        $app['config']->set('app.locale', 'tw');
        $app['config']->set('database.default', 'pgsql');
        $app['config']->set('database.connections.pgsql', [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'testing_db'),
            'username' => env('DB_USERNAME', ''),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',
            'sslmode' => 'prefer',
        ]);
        $app['config']->set('wallet.member-model', TestMember::class);
    }

    protected function defineDatabaseMigrations() {
        if (!RefreshDatabaseState::$migrated) {
            DB::statement('DROP SEQUENCE IF EXISTS base_id CASCADE');
            DB::statement('DROP SEQUENCE IF EXISTS base_ranking CASCADE');
        }

        $this->loadMigrationsFrom(__DIR__ . '/../vendor/matrix-platform/laravel-base/database/migrations');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function currency(string $code = 'USD', int $precision = 2, ?int $ranking = null, ?Carbon $enable = null, ?Carbon $disable = null): Currency {
        $attrs = [
            'code' => $code,
            'disable_time' => $disable,
            'enable_time' => $enable ?? now()->subMinute(),
            'precision' => $precision,
            'title' => $code,
        ];

        if ($ranking !== null) {
            $attrs['ranking'] = $ranking;
        }

        return Currency::forceCreate($attrs);
    }

    protected function member(?string $username = null): TestMember {
        return TestMember::forceCreate(['username' => $username ?? 'u' . uniqid()]);
    }

    protected function wallet(float $balance = 0, float $frozen = 0, int $precision = 2, string $code = 'USD', ?TestMember $member = null): Wallet {
        $member ??= $this->member();

        if (!Currency::where('code', $code)->exists()) {
            $this->currency($code, $precision);
        }

        $wallet = Wallets::get($member, $code);
        $wallet->balance = $balance;
        $wallet->frozen = $frozen;
        $wallet->save();
        $wallet->refresh();
        $wallet->setRelation('member', $member);

        return $wallet;
    }

}
