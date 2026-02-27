<?php //>

namespace MatrixPlatform;

use Illuminate\Support\ServiceProvider;

class WalletServiceProvider extends ServiceProvider {

    public function boot() {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }
    }

    public function register() {
        if (!$this->app->configurationIsCached()) {
            foreach (glob(__DIR__ . '/../config/*.php') as $file) {
                $this->mergeConfigFrom($file, basename($file, '.php'));
            }
        }

        $this->app->singleton("PackageInfo:wallet", fn () => new PackageInfo(dirname(__DIR__)));
    }

}
