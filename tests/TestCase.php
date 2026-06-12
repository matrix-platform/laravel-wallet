<?php //>

namespace Tests;

use MatrixPlatform\BaseServiceProvider;
use MatrixPlatform\WalletServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase {

    protected function getPackageProviders($app) {
        return [
            BaseServiceProvider::class,
            WalletServiceProvider::class,
        ];
    }

}
