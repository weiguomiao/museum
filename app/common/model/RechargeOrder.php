<?php
declare (strict_types = 1);

namespace app\common\model;

use app\BaseModel;
use think\Model;

/**
 * @mixin think\Model
 */
class RechargeOrder extends BaseModel
{
    //1微信直充,2微信间充，3卡充
    public function getTypeValAttr($v,$d){
        $val=[1=>'微信直充',2=>'微信间充',3=>'卡充值'];
        return $val[$d['type']];
    }
}
