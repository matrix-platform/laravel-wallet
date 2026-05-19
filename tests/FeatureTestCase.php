<?php //>

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;

class FeatureTestCase extends TestCase {

    use RefreshDatabase;

    protected function defineEnvironment($app) {
        $app['config']->set('app.key', 'base64:' . base64_encode(str_repeat('a', 32)));
        $app['config']->set('database.default', 'pgsql');
        $app['config']->set('database.connections.pgsql', [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'laravel_wallet_test'),
            'username' => env('DB_USERNAME', ''),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',
            'sslmode' => 'prefer',
        ]);
    }

    protected function defineDatabaseMigrations() {
        if (! RefreshDatabaseState::$migrated) {
            DB::statement('DROP SEQUENCE IF EXISTS base_id CASCADE');
            DB::statement('DROP SEQUENCE IF EXISTS base_ranking CASCADE');
        }
        $this->loadMigrationsFrom(__DIR__ . '/../vendor/matrix-platform/laravel-base/database/migrations');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

}
