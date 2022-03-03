<?php
declare (strict_types=1);

namespace app\api\controller;

use app\common\exception\AppRuntimeException;
use app\common\model\RefundOrder;
use app\common\service\RefundService;
use EasyWeChat\Factory;
use think\facade\Log;

/**
 * 微信退款通知
 * Class RefundNotifyController
 * @package app\api\controller
 */
class RefundNotifyController
{
    //微信退款回调
    public function notify()
    {
        $payment = Factory::payment(config('wechat.payment'));
        try {
            $response = $payment->handleRefundedNotify(function ($message, $reqInfo, $fail) {
                //throw new AppRuntimeException('支付回调信息', $message, 1000);
                // 使用通知里的 "微信支付订单号" 或者 "商户订单号" 去自己的数据库找到订单
                $order = RefundOrder::find($reqInfo['out_refund_no']);
                if (empty($order) || $order->status != 2) { // 如果订单不存在 或者 订单已经支付过了
                    return true; // 告诉微信，我已经处理完了，订单没找到，别再通知我了
                }
                ///////////// <- 建议在这里调用微信的【订单查询】接口查一下该笔订单的情况，确认是已经支付 /////////////
                if ($message['return_code'] === 'SUCCESS' && $reqInfo['refund_status'] === 'SUCCESS') { // return_code 表示通信状态，不代表支付状态
                    Log::record($message,'refund');
                    $order->status = 1;
                    $order->accomplish_time = time();
                } else {
                    $order->status = 3;
                    $order->accomplish_time = time();
                }
                $order->trade_no = $reqInfo['out_trade_no'];
                $order->save(); // 保存订单
                return true; // 返回处理完成
            });
        } catch (\Exception $e) {
            Log::write($e->getMessage() . '__' . $e->getFile() . '__' . $e->getLine());
        }
        if (isset($response)) {
            return $response;
        } else {
            return true;
        }
    }
}
