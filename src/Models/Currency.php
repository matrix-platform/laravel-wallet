<?php //>

namespace MatrixPlatform\Models;

class Currency extends BaseModel {

    protected $casts = [
        'enable_time' => 'datetime',
        'disable_time' => 'datetime'
    ];

    protected $table = 'base_currency';

    public function round($value) {
        return round($value, $this->precision);
    }

}
