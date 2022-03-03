<?php
declare (strict_types = 1);

namespace app\common\model;

use think\Model;

/**
 * @mixin think\Model
 */
class OrderGoods extends Model
{
    //
    public function getOrderIdAttr($v){
        return (string)$v;
    }
}
