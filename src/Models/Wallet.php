<?php //>

namespace MatrixPlatform\Models;

use MatrixPlatform\Support\Wallets;

class Wallet extends BaseModel {

    const TRACEABLE = false;

    protected $alias = 'wallets';

    protected $attributes = [
        'balance' => 0,
        'frozen' => 0
    ];

    protected $casts = [
        'balance' => 'float',
        'frozen' => 'float'
    ];

    protected $parent = 'member';

    protected $table = 'base_wallet';

    public function currency() {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function freeze($type, $amount, $remark = null, $data = null) {
        return Wallets::freeze($this, $type, $amount, $remark, $data);
    }

    public function logs() {
        return $this->hasMany(WalletLog::class, 'wallet_id');
    }

    public function manipulate($type, $amount, $remark = null, $data = null) {
        return Wallets::manipulate($this, $type, $amount, $remark, $data);
    }

    public function member() {
        return $this->belongsTo(config('matrix.member-model'), 'member_id');
    }

    public function toTitle() {
        return $this->currency->toTitle();
    }

}
