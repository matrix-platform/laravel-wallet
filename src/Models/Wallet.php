<?php //>

namespace MatrixPlatform\Models;

use MatrixPlatform\Support\Wallets;
use MatrixPlatform\Traits\Traceable;

class Wallet extends BaseModel {

    use Traceable;

    const UPDATED_AT = 'modify_time';

    protected $attributes = ['balance' => 0, 'frozen' => 0];
    protected $table = 'base_wallet';

    public function currency() {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function freeze($type, $amount, $remark = null, $data = null) {
        return Wallets::freeze($this, $type, $amount, $remark, $data);
    }

    public function manipulate($type, $amount, $remark = null, $data = null) {
        return Wallets::manipulate($this, $type, $amount, $remark, $data);
    }

    public function member() {
        return $this->belongsTo(Member::class, 'member_id');
    }

}
