<?php
declare (strict_types = 1);

namespace app\common\model;

use app\BaseModel;
use app\common\getAttr\StatusAttr;
use think\Model;

/**
 * @mixin think\Model
 */
class TicketOrder extends BaseModel
{
    use StatusAttr;
    const STATUS = [
        '1' => [1, '待核销'],
        '2' => [2, '待支付 '],
        '3' => [3, '已核销 '],
        '4' => [4, '退款 '],
    ];

    //获取订单ID
    public function getIdAttr($v){
        return (string)$v;
    }

    protected $type = [
        'book_time'=>'timestamp:Y-m-d'
    ];

    public function getTicketNameAttr($v,$d){
        return Ticket::where('id',$d['ticket_id'])->value('title');
    }

    public function getRefundOrderAttr($v,$d){
        if($d['status']==4){
            return RefundOrder::where('order_id',$d['id'])->append(['status_val'])->find();
        }else
            return null;
    }
}
