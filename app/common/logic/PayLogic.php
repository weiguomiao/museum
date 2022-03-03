<?php


namespace app\common\logic;

use app\common\model\GoodsDetail;
use app\common\model\Order;
use app\common\model\OrderGoods;
use app\common\model\PayRecord;
use app\common\model\Qrcode;
use app\common\model\RechargeOrder;
use app\common\model\TicketOrder;
use app\common\model\User;
use app\common\model\UserBalanceLog;
use app\common\service\SendMessageService;
use EasyWeChat\Factory;
use mytools\lib\ToolBag;
use think\facade\Log;

class PayLogic
{
    /**获取支付参数
     * @param $openid
     * @param $price
     * @param $out_trade_no
     * @return array|string
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function getwechatjsdk($openid, $price, $out_trade_no)
    {

        $app = Factory::payment(config('wechat.payment'));
        $result = $app->order->unify([
            'body' => '订单号：' . $out_trade_no,
            'out_trade_no' => $out_trade_no,
            'total_fee' => round($price * 100),
            'notify_url' => 'http://huananmuseum.sdream.top/api/notify', // 支付结果通知网址，如果不设置则会使用配置里的默认地址
            'trade_type' => 'JSAPI', // 请对应换成你的支付方式对应的值类型
            'openid' => $openid,
        ]);
//        Log::record($result,'error');
        $jsdk = $app->jssdk->bridgeConfig($result['prepay_id'], false);
        return $jsdk;
    }

    /**微信退款
     * @param $transactionId
     * @param $order_id
     * @param $total_amount
     * @param $amount
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     */
    public static function refund($transactionId,$order_id,$total_amount,$amount)
    {
        $app = Factory::payment(config('wechat.payment'));
        // 参数分别为：微信订单号、商户退款单号、订单金额、退款金额、其他参数
        $app->refund->byTransactionId($transactionId,  $order_id,  round($total_amount * 100), round($amount * 100), [
            'notify_url'=>'http://huananmuseum.sdream.top/api/refundNotify'
        ]);
    }

    /**余额支付
     * @param $order
     * @param $paylog
     * @return string
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function balancePay($order, $paylog)
    {
        $user = User::find($order['user_id']);
        if ($user->balance < $order['total_amount']) return '';
        $user->balance = ['dec', $order['total_amount']];
        $user->save();
        $paylog->status = 1;
        $paylog->money = $order['total_amount'];
        $paylog->save();
        self::balanceLog($order['total_amount'], 1, $user->id, 2, '订单号：' . $order->id, $order->id);
        self::updateOrder($paylog);
        return '支付成功';
    }

    /**用户金额变动记录
     * @param $amount --金额
     * @param $type --类型 1购物，2微信充值，3卡充值 4线下支付 5充值赠送 6门票购买 7门票退款
     * @param $uid --用户id
     * @param $cashier --状态 1收入，2支出
     * @param $remark --备注
     * @param $extned --额外参数
     */
    public static function balanceLog($amount, $type, $uid, $cashier, $remark, $extned)
    {
        UserBalanceLog::create([
            'amount' => $amount,
            'type' => $type,
            'user_id' => $uid,
            'cashier' => $cashier,
            'remark' => $remark,
            'extend' => $extned
        ]);
    }

    /**扫码支付记录
     * @param $paylog
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function payRecord($paylog)
    {
        $qrcode = Qrcode::where('id', $paylog->extend)->find();
        PayRecord::create([
            'id' => $paylog->id,
            'type' => $paylog->type,
            'user_id' => $paylog->user_id,
            'qr_id' => $paylog->extend,
            'money' => $paylog->money,
            'to_user_id' => $qrcode->user_id
        ]);
        $content = [
            'time' => time(),
            'type' => $paylog->type == 1 ? '微信支付' : '余额支付',
            'order_id' => $paylog->id,
            'money' => $paylog->money
        ];
        $openid = self::getOpenid($qrcode->user_id);
        if (!empty($openid)) {
            (new SendMessageService())->collectNotify($openid, $content);
        }
    }

    /**获取openID
     * @param $uid
     * @return mixed
     */
    public static function getOpenid($uid)
    {
        return User::where('id', $uid)->value('openid');
    }

    /**
     * 改变订单状态
     * @param $payLog
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function updateOrder($payLog)
    {
        if ($payLog->mode == 1) {//线上消费
            if ($payLog->type == 1 || $payLog->type == 2) {//微信支付和余额支付修改订单状态和增加销量
                $order = Order::where('id', $payLog->order_id)->where('user_id', $payLog->user_id)->find();
                $order->status = 1;
                $order->pay_time = time();
                $order->save();
                //增加销量
                $orderGoods = OrderGoods::where('order_id', $order->id)->select();
                foreach ($orderGoods as $v) {
                    $goods = GoodsDetail::where('id', $v['goods_id'])->find();
                    $goods->sales = ['inc', $v['num']];
                    $goods->save();
                }
            }
            if ($payLog->type == 3) {
                $order = RechargeOrder::where('id', $payLog->order_id)->where('user_id', $payLog->user_id)->find();
                $order->status = 1;
                $order->pay_time = time();
                $order->save();
                $user = User::where('id', $order->user_id)->find();
                $user->balance = ['inc', $order->money];
                $user->save();
                PayLogic::balanceLog($order->money, 2, $order->user_id, 1, '订单号' . $order->id, $order->id);
                if ($order->type == 2) {
                    $user->balance = ['inc', $order->extend];
                    PayLogic::balanceLog($order->extend, 5, $order->user_id, 1, '订单号' . $order->id, $order->id);
                }
                $user->save();
            }
            //修改门票订单状态
            if ($payLog->type == 4) {
                Log::record($payLog, 'ticket');
                $extend = json_decode($payLog->extend, true);
                for ($i = 1; $i <= $payLog['num']; $i++) {
                    TicketOrder::create([
                        'id' => ToolBag::ymdUniqueId(),
                        'ticket_id' => $extend['ticket_id'],
                        'user_id' => $payLog['user_id'],
                        'name' => $extend['name'],
                        'mobile' => $extend['mobile'],
                        'amount' => $extend['price'],
                        'book_time' => $extend['book_time'],
                        'create_time' => time(),
                        'status' => 1,
                        'pay_id' => $payLog->id,
                        'trade_no' => $payLog->transaction_id
                    ]);
                }
            } else {  //线下消费
                if ($payLog->type == 1) {//微信支付
//                PayLogic::balanceLog($payLog->money,4,$payLog->user_id,2,'订单号：'.$payLog->order_id,$payLog->id);
                    PayLogic::payRecord($payLog);
                }
            }
        }
    }
}