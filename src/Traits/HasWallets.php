<?php //>

namespace MatrixPlatform\Traits;

use MatrixPlatform\Models\Wallet;
use MatrixPlatform\Support\Wallets;

trait HasWallets {

    public function getWalletsAttribute() {
        if (!$this->relationLoaded("wallets")) {
            $this->setRelation("wallets", Wallets::list($this));
        }

        return $this->getRelation("wallets");
    }

    public function wallet($currency) {
        return Wallets::get($this, $currency);
    }

    public function wallets() {
        return $this->hasMany(Wallet::class, 'member_id');
    }

}
