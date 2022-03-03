<?php
declare (strict_types = 1);

namespace app\common\model;

use app\BaseModel;
use think\Model;

/**
 * @mixin think\Model
 */
class Order extends BaseModel
{
    protected $json = ['address_info', 'post_info'];
    protected $jsonAssoc = true;

    protected $type = [
        'goods_price'          => 'float',
        'order_amount'          => 'float',
        'pay_time'=>'timestamp:Y-m-d H:i:s',
        'post_send_time'=>'timestamp:Y-m-d H:i:s',
        'post_id'=>'integer'
    ];

    public function getIdAttr($v)
    {
        return (string)$v;
    }

    public function getStatusValAttr($v,$d)
    {
        $status = [1 =>'待发货', 2=>'待付款',3=>'待收货',4=>'已完成',5=>'已取消'];
        return $status[$d['status']];
    }

    //
    public function getOrderGoodsAttr($v,$d){
        return OrderGoods::where('order_id',$d['id'])->visible(['id','goods_id','image','price','num','spec_name','goods_name'])->select();
    }

    public function getPostNameAttr($v,$d){
        return PostType::where('id',$d['post_id'])->value('name');
    }
}
