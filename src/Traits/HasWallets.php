<?php //>

namespace MatrixPlatform\Traits;

use MatrixPlatform\Support\Wallets;

trait HasWallets {

    public function getWalletsAttribute() {
        return $this->relations['wallets'] ??= Wallets::list($this);
    }

}
