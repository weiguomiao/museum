<?php
declare (strict_types = 1);

namespace app\common\model;

use think\Model;

/**
 * @mixin think\Model
 */
class RefundOrder extends Model
{
    //
    public function getIdAttr($v){
        return (string)$v;
    }

    public function getOrderIdAttr($v){
        return (string)$v;
    }

    public function getStatusValAttr($v,$d){
        $status=[1=>'退款成功',2=>'退款中',3=>'退款失败'];
        return $status[$d['status']];
    }

    protected $type = [
        'accomplish_time'=>'timestamp:Y-m-d H:i:s'
    ];
}
