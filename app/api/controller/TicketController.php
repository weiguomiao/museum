<?php
declare (strict_types=1);

namespace app\api\controller;

use app\BaseController;
use app\common\logic\PayLogic;
use app\common\model\Calendar;
use app\common\model\Guide;
use app\common\model\Order;
use app\common\model\PayLog;
use app\common\model\RefundOrder;
use app\common\model\Ticket;
use app\common\model\TicketOrder;
use mytools\lib\ToolBag;
use think\Request;
use think\route\Rule;

class TicketController extends BaseController
{
    /**预约须知和收费标准
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function guide()
    {
        $data = Guide::select();
        return self::success($data);
    }

    /**门票列表
     * @return \think\response\Json
     * @throws \think\db\exception\DbException
     */
    public function ticketList()
    {
        $list = Ticket::where('status', 1)
            ->order('create_time desc')
            ->visible(['id', 'title', 'content', 'price'])
            ->paginate(10, false);
        return self::success($list);
    }

    /**门票信息
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function ticketInfo(){
        $input=$this->postValidate([
            'ticket_id|门票ID'=>'require'
        ]);
        $ticket=Ticket::where('id',$input['ticket_id'])->find();
        return self::success($ticket);
    }

    /**选择日期
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function calendarChoice()
    {
        $list = Calendar::where('time', 'between', [time(), 30 * 86400 + time()])
            ->withAttr('time', function ($v) {
                return date('Y-m-d',$v);
            })->select();
        return self::success($list);
    }

    /**购买日期核查 $book_time时间戳
     * @param $book_time
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private static function dateCheck($book_time){
        $start=strtotime(date('Y-m-d'));
        $end=86400*30+strtotime(date('Y-m-d'));
        if($book_time<$start||$book_time>$end) return false;
        $cal=Calendar::where('time',$book_time)->find();
        if(!empty($cal)){
            if($cal->status==1) return true;
            else false;
        }else{
            $xingqi = date("w", $book_time);
            if ($xingqi == 1) return false;
            else return true;
        }
    }

    /**创建订单
     * @param Request $request
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function createOrder(Request $request)
    {
        $user = $request->user;
        $input = $this->postValidate([
            'ticket_id|门票ID' => 'require|number',
            'num|购买数量' => 'require|number|egt:1|elt:10',
            'book_time|预约时间' => 'require',//2021-12-15
            'name|姓名' => 'require',
            'mobile|手机号' => 'require|mobile'
        ]);
        $ticket = Ticket::where('id', $input['ticket_id'])->where('status', 1)->find();
        if (empty($ticket)) return self::error('门票已下架！');
        $book_time = strtotime($input['book_time']);
        $check=self::dateCheck($book_time);
        if($check==false) return self::error('该日期不能预约哦，请选择其它日期！');
        $re=PayLog::create([
            'id'=>ToolBag::timeUniqueId(),
            'user_id'=>$user->id,
            'order_id'=>ToolBag::ymdUniqueId(),
            'type'=>4,
            'num'=>$input['num'],
            'money'=>round($input['num']*$ticket->price,2),
            'mode'=>1,
            'extend'=>json_encode([
                'ticket_id'=>$ticket->id,
                'book_time'=>$book_time,
                'name'=>$input['name'],
                'mobile'=>$input['mobile'],
                'price'=>$ticket->price
            ],512)
        ]);
        return self::success($re->id);
    }

    /**门票订单列表
     * @param Request $request
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DbException
     */
    public function orderList(Request $request)
    {
        $input = $this->postValidate([
            'status' => 'in:1,2,3,4',//1待使用 2待支付 3使用成功 4退款 5已取消
        ]);
        $w['user_id'] = $request->user_id;
        if (!empty($input['status'])) $w['status'] = $input['status'];
        $list = TicketOrder::where($w)->append(['status_val','ticket_name'])->order('create_time', 'desc')->paginate(10, false);
        return self::success($list);
    }

    /**门票订单详情
     * @param Request $request
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function orderInfo(Request $request)
    {
        $input = $this->postValidate([
            'order_id|订单ID' => 'require'
        ]);
        $w['user_id'] = $request->user_id;
        $w['id'] = $input['order_id'];
        $order = TicketOrder::where($w)->append(['status_val','ticket_name','refund_order'])->find();
        return self::success($order);
    }

    /**修改订单时间
     * @param Request $request
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function updateTime(Request $request)
    {
        $input = $this->postValidate([
            'order_id|订单ID' => 'require',
            'book_time|预约时间' => 'require'
        ]);
        $w['user_id'] = $request->user_id;
        $w['id'] = $input['order_id'];
        $book_time = strtotime($input['book_time']);
        $check=self::dateCheck($book_time);
        if($check==false) return self::error('您选择的日期不能预约哦，请选择其它时间！');
        $order = TicketOrder::where($w)->find();
        if (empty($order)) return self::error('订单异常！');
        if ($order->status != 1) return self::error('该订单状态不可修改！');
        $order->book_time = $book_time;
        $order->save();
        return self::success('修改时间成功！');
    }

    /**核销今日订单列表
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DbException
     */
    public function useOrderList(Request $request){
        $list=TicketOrder::where('status',1)
            ->where('user_id',$request->user_id)
            ->where('book_time',strtotime(date('Y-m-d')))
            ->append(['status_val','ticket_name'])
            ->order('book_time asc')
            ->paginate(10,false);
        return self::success($list);
    }

    /**门票订单核销
     * @param Request $request
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function useTicket(Request $request)
    {
        $input = $this->postValidate([
            'order_id|订单ID'=>'require',
        ]);
        $order=TicketOrder::where('id',$input['order_id'])->where('status',1)->find();
        if(empty($order)) return self::error('请勿重复核销订单');
        $order->status=3;
        $order->save();
        return self::success('门票核销成功！');
    }

    /**门票订单退款
     * @param Request $request
     * @return \think\response\Json
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function refund(Request $request)
    {
        $input=$this->postValidate([
            'order_id|订单ID'=>'require',
        ]);
        $order=TicketOrder::where('id',$input['order_id'])->find();
        if(empty($order)) return self::error('订单异常！');
        if($order->status!=1) return self::error('订单状态异常，请勿重复提交！');
        $order->status=4;
        $order->save();
        $order_id=ToolBag::ymdUniqueId();
        RefundOrder::create([
            'id'=>$order_id,
            'user_id'=>$request->user_id,
            'order_id'=>$order->id,
            'amount'=>$order->amount,
            'remark'=>'门票退款'
        ]);
        $payLog=PayLog::where('id',$order->pay_id)->find();
        PayLogic::refund($order->trade_no,$order_id,$payLog->money,$order->amount);
        return self::success('提交成功！');
    }
}
