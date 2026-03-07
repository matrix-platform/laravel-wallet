<?php //>

namespace MatrixPlatform\Models;

use MatrixPlatform\Traits\Traceable;

class Currency extends BaseModel {

    use Traceable;

    protected $casts = ['enable_time' => 'datetime', 'disable_time' => 'datetime'];
    protected $table = 'base_currency';

}
