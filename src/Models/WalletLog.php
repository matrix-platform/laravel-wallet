<?php //>

namespace MatrixPlatform\Models;

class WalletLog extends BaseModel {

    const UPDATED_AT = null;
    const UPDATED_BY = null;

    const TRACEABLE = false;

    protected $casts = [
        'amount' => 'float',
        'balance' => 'float',
        'data' => 'array'
    ];

    protected $parent = 'wallet';

    protected $table = 'base_wallet_log';

    public function wallet() {
        return $this->belongsTo(Wallet::class, 'wallet_id');
    }

}
