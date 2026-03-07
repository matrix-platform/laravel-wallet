<?php //>

namespace MatrixPlatform\Models;

use MatrixPlatform\Traits\Traceable;

class Wallet extends BaseModel {

    use Traceable;

    const UPDATED_AT = 'modify_time';

    protected $attributes = ['balance' => 0, 'frozen' => 0];
    protected $table = 'base_wallet';

    public function currency() {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function member() {
        return $this->belongsTo(Member::class, 'member_id');
    }

}
