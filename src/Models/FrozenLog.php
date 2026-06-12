<?php //>

namespace MatrixPlatform\Models;

class FrozenLog extends BaseModel {

    const UPDATED_AT = null;
    const UPDATED_BY = null;

    const TRACEABLE = false;

    protected $casts = [
        'amount' => 'float',
        'data' => 'array'
    ];

    protected $parent = 'wallet';

    protected $table = 'base_frozen_log';

    public function wallet() {
        return $this->belongsTo(Wallet::class, 'wallet_id');
    }

}
