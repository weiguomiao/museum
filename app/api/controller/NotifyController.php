<?php
declare (strict_types = 1);

namespace app\api\controller;

use app\BaseController;
use app\common\exception\AppRuntimeException;
use app\common\logic\PayLogic;
use app\common\model\Course;
use app\common\model\CourseOrder;
use app\common\model\GoodsDetail;
use app\common\model\Order;
use app\common\model\OrderGoods;
use app\common\model\PayLog;
use app\common\model\RechargeOrder;
use app\common\model\TicketOrder;
use app\common\model\ToolsMs;
use app\common\model\User;
use app\common\model\UserRecharge;
use app\common\model\UserRetail;
use EasyWeChat\Factory;
use think\facade\Db;
use think\facade\Log;

class NotifyController extends BaseController
{
    /**
     * 微信支付回调
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \EasyWeChat\Kernel\Exceptions\Exception
     */
    public function notify()
    {
        $app = Factory::payment(config('wechat.payment'));
        return $app->handlePaidNotify(function ($message, $fail) {
            $payLog = PayLog::find($message['out_trade_no']);
            //Log::record($payLog,'error');
            if (empty($payLog)|| $payLog['status'] !== 2) return true;//订单不存在或已支付完成就别再通知我了
            if ($message['return_code'] !== 'SUCCESS') return $fail('通信失败，请稍后再通知我');
            if ($message['result_code'] !== 'SUCCESS') return true;//支付失败，别再通知我了
            if($message['result_code']==='SUCCESS'){
                //支付成功
                Log::record($message,'message');
                if(!empty($message['result_code'])&&$message['result_code']==='SUCCESS'){
                    $payLog->status=1;
                    $payLog->transaction_id=$message['transaction_id'];
                    $payLog->save();
                    //改变状态
                    PayLogic::updateOrder($payLog);
                    //支付失败
                }elseif(!empty($message['result_code'])&&$message['result_code']==='FAIL'){
                    $payLog->status=3;
                    $payLog->save();
                    throw new AppRuntimeException('支付失败', $message, 1007);
                }else{
                    return $fail('通信失败，请稍后再通知我');
                }
            }
            return true;
        });
    }



    /**
     * 获取订单状态
     * @return \think\response\Json
     * @throws AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getStatus(){
        $params=$this->paramsValidate([
            'order_id'=>'require',
            'type'=>'require|in:1,2,3,4'//1商品订单，2充值订单 3扫码支付 4门票
        ]);
        switch ($params['type']){
            case 1:
                $order=Order::where('id',$params['order_id'])->where('user_id',$this->request->user_id)->find();
                break;
            case 2:
                $order=RechargeOrder::where('id',$params['order_id'])->where('user_id',$this->request->user_id)->find();
                break;
            case 3:
            case 4:
                $order=PayLog::where('id',$params['order_id'])->where('user_id',$this->request->user_id)->find();
                break;
        }
        $data=[
            'status'=>$order->status
        ];
        return self::success($data);
    }

}
