<?php
declare (strict_types = 1);

namespace app\common\model;

use app\BaseModel;
use think\Model;

/**
 * @mixin think\Model
 */
class GoodsSpec extends BaseModel
{
    //
    protected $pk='spec_id';

    protected $type = [
        'spec_price'    =>  'float'
    ];
}
