<?php
declare (strict_types = 1);

namespace app\common\model;

use app\BaseModel;
use think\Model;

/**
 * @mixin think\Model
 */
class UserBalanceLog extends BaseModel
{
    //
    public function getTypeValAttr($v,$d){
        //1网上购物，2微信充值，3卡充值，4线下支付 5充值赠送 6门票购买 7门票退款
        $status=[1=>'网上购物',2=>'微信充值',3=>'卡充值',4=>'线下支付',5=>'充值赠送',6=>'门票购买',7=>'门票退款'];
        return $status[$d['type']];
    }

    //
    public function getCashierValAttr($v,$d){
        //1收入 2支出
        $status=[1=>'收入',2=>'支出'];
        return $status[$d['cashier']];
    }
}
