<?php //>

namespace MatrixPlatform\Models;

class FrozenLog extends BaseModel {

    const CREATED_AT = 'create_time';

    protected $casts = ['data' => 'json'];
    protected $table = 'base_frozen_log';

}
