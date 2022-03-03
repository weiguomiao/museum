<?php
declare (strict_types = 1);

namespace app\common\model;

use app\BaseModel;
use think\Model;

/**
 * @mixin think\Model
 */
class CardActivationLog extends BaseModel
{
    //
    protected $type = [
        'expire_time'=>'timestamp:Y-m-d',
    ];
}
