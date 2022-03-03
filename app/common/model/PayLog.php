<?php
declare (strict_types = 1);

namespace app\common\model;

use app\BaseModel;
use think\Model;

/**
 * @mixin think\Model
 */
class PayLog extends BaseModel
{
    //1微信支付，2余额支付，3充值 4门票购买
    public function getTypeValAttr($v,$d){
        $val=[1=>'微信支付',2=>'余额支付',3=>'充值',4=>'购买门票'];
        return $val[$d['type']];
    }

    public function getModeValAttr($v,$d){
        $val=[1=>'线上消费',2=>'线下消费'];
        return $val[$d['mode']];
    }
}
