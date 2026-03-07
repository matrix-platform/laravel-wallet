<?php //>

namespace MatrixPlatform\Models;

class WalletLog extends BaseModel {

    const CREATED_AT = 'create_time';

    protected $casts = ['data' => 'json'];
    protected $table = 'base_wallet_log';

}
