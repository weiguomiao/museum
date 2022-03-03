<?php
declare (strict_types = 1);

namespace app\api\controller;

use app\BaseController;
use app\common\logic\OrderLogic;
use app\common\logic\PayLogic;
use app\common\model\Config;
use app\common\model\GoodsDetail;
use app\common\model\GoodsSpec;
use app\common\model\Order;
use app\common\model\OrderGoods;
use app\common\model\PayLog;
use app\common\model\PostType;
use app\common\model\RechargeOrder;
use app\common\model\TicketOrder;
use app\common\model\User;
use app\common\model\UserAddress;
use app\common\service\ApiClientService;
use mytools\lib\ToolBag;
use mytools\lib\ValidateTool;
use think\Request;

class OrderController extends BaseController
{
    /**订单结算
     * @param Request $request
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function orderDisplay(Request $request){
        $params=$this->paramsValidate([
            'cart|购物车'=>'require',//购物车数组id,num,规格spec_id
        ]);
        $info=OrderLogic::isTop($params['cart']);
        if(!empty($info)) return self::error($info);
        $uid=$request->user_id;
        $total=OrderLogic::comTotal($params['cart']);
        $addr=UserAddress::where('user_id',$uid)->where('is_default',1)->find();
        if(empty($addr)){
            $addr=UserAddress::where('user_id',$uid)->find();
        }
        $data=[
            'total'=>$total,
            'addr'=>empty($addr)?'':$addr,
        ];
        return self::success($data);
    }

    /**创建订单
     * @param Request $request
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function createOrder(Request $request){
        $params=$this->paramsValidate([
            'addr_id|地址'=>'require',
            'cart|购物车'=>'require',//商品信息数组:id,数量num,规格spec_id
            'user_note|用户备注'=>''
        ]);
        $info=OrderLogic::isTop($params['cart']);
        if(!empty($info)) return self::error($info);
        $total=OrderLogic::comTotal($params['cart']);
        if(empty($params['addr_id'])) return self::error('请添加收货地址！');
        $addrInfo=UserAddress::where('id',$params['addr_id'])->where('user_id',$request->user_id)->find();
        if(empty($addrInfo)) return self::error('地址错误，请重新填写地址！');
        //生成待付款订单
        $order_data=[
            'id'=>ToolBag::ymdUniqueId(),
            'user_id'=>$request->user_id,
            'address_info'=> [
                'name'=>$addrInfo['name'],
                'tel'=>$addrInfo['tel'],
                'area'=>$addrInfo['area'],
                'address'=>$addrInfo['address'],
            ],
            'user_note'=>$params['user_note'],
            'goods_price'=>$total['total_price'],
            'postage'=>$total['ship'],
            'total_amount'=>$total['total_amount'],
        ];
        $order=Order::create($order_data);
        //向订单商品表添加多条数据
        $order_goods_data = [];
        foreach($params['cart'] as $v){
            $goodsInfo=GoodsDetail::where('id',$v['id'])->find();
            $spec=GoodsSpec::where('spec_id',$v['spec_id'])->find();
            $row = [
                'order_id' => $order['id'],
                'goods_id' => $v['id'],
                'num' => $v['num'],
                'goods_name' => $goodsInfo->name,
                'image' => $goodsInfo->image,
                'price' => $spec['spec_price'],
                'spec_name'=>$spec['spec_name']
            ];
            $order_goods_data[] = $row;
        }
        (new OrderGoods())->insertAll($order_goods_data);
        $viewData=[
            'order_id'=>$order->id
        ];
        return self::success($viewData);
    }

    /**支付
     * @param Request $request
     * @return string|\think\response\Json
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function pay(Request $request)
    {
        $params = $this->paramsValidate([
            'order_id|订单号' => 'require',//订单号
            'type|支付方式' => 'require|in:1,2,3,4' //1是微信支付,2是余额支付,3微信充值,4门票购买
        ]);
        $userInfo=User::where('id',$request->user_id)->find();
        switch ($params['type']){
            case 1:
                $payid=ToolBag::ymdUniqueId();
                $paylog=PayLog::create([
                    'id'=>$payid,
                    'order_id'=>$params['order_id'],
                    'type'=>$params['type'],
                    'user_id'=>$request->user_id,
                    'mode'=>1
                ]);
                if(!$paylog) return '支付订单创建异常';
                $order = Order::where('id', $params['order_id'])->find();
                if ($order->getData('status') !== 2) return self::error('订单状态异常');
                if (!ValidateTool::isMoney($order['total_amount'])&&$order['total_amount']<=0) return self::error('金额异常！');
                $paylog->money=$order->total_amount;
                $paylog->save();
                $jsdk = PayLogic::getwechatjsdk($userInfo->openid, $order->total_amount, $payid);
                return self::success($jsdk);
                break;
            case 2:
                $payid=ToolBag::ymdUniqueId();
                $paylog=PayLog::create([
                    'id'=>$payid,
                    'order_id'=>$params['order_id'],
                    'type'=>$params['type'],
                    'user_id'=>$request->user_id,
                    'mode'=>1
                ]);
                if(!$paylog) return '支付订单创建异常';
                $order = Order::where('id', $params['order_id'])->find();
                if ($order->getData('status') !== 2) return self::error('订单状态异常');
                $pay=PayLogic::balancePay($order,$paylog);
                if(empty($pay)) return self::error('余额不足！');
                return self::success($pay);
                break;
            case 3:
                $payid=ToolBag::ymdUniqueId();
                $paylog=PayLog::create([
                    'id'=>$payid,
                    'order_id'=>$params['order_id'],
                    'type'=>$params['type'],
                    'user_id'=>$request->user_id,
                    'mode'=>1
                ]);
                if(!$paylog) return '支付订单创建异常';
                $order = RechargeOrder::where('id', $params['order_id'])->find();
                if ($order->status !== 2) return self::error('订单状态异常');
                $paylog->money=$order->money;
                $paylog->save();
                $jsdk = PayLogic::getwechatjsdk($userInfo->openid, $order->money, $payid);
                return self::success($jsdk);
                break;
            case 4:
                $order=PayLog::where('id',$params['order_id'])->where('status',2)->find();
                if(empty($order)) return self::error('订单已支付，操作异常！');
                $jsdk = PayLogic::getwechatjsdk($userInfo->openid, $order->money, $order->id);
                return self::success($jsdk);
                break;
            default:
                return self::error('参数错误！');
        }
    }

    /**订单列表
     * @param Request $request
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DbException
     */
    public function orderList(Request $request){
        $input = $this->paramsValidate([
            'status' => 'in:1,2,3,4'
        ]);
        $w['user_id'] = $request->user_id;
        if (!empty($input['status'])) $w['status'] = $input['status'];
        $list = Order::where($w)->order('create_time', 'desc')
            ->append(['status_val','orderGoods'])
            ->visible(['id','orderGoods', 'total_amount', 'status', 'status_val', 'create_time'])
            ->paginate(10, false);
        return self::success($list);
    }

    /**订单详情
     * @param Request $request
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function orderInfo(Request $request)
    {
        $input = $this->paramsValidate([
            'order_id|订单号' => 'require|integer|length:18'
        ]);
        $w['user_id'] = $request->user_id;
        $w['id'] = $input['order_id'];
        $order = Order::where($w)->withAttr('post_id',function ($v){
            return PostType::find($v)['name'];
        })->append(['status_val','orderGoods'])
            ->visible(['id', 'orderGoods', 'total_amount', 'status', 'status_val', 'address_info', 'pay_time', 'create_time','post_number','post_id','user_note'])
            ->find()->toArray();
        if (!$order) return self::error('订单号非法');
        $order['mobile'] = Config::find('mobile')['value'];
        $order['address']=Config::find('address')['value'];
        return self::success($order);
    }

    /**
     * 快递详情
     * @param Request $request
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function orderPost(Request $request)
    {
        $input = $this->paramsValidate([
            'order_id|订单号' => 'require|integer|length:18'
        ]);
        $w['user_id'] = $request->user_id;
        $w['id'] = $input['order_id'];
        $order = Order::where($w)->find();
        if (!$order) return self::error('订单号非法');
        if (!in_array($order['status'], [3, 4])) return self::error('订单未发货');
        $post_type=PostType::where('id',$order->post_id)->find();
        if ($order['status'] == 3 && (time() - $order['post_query_time']) > 900) {
            $post_info = (new ApiClientService())->postInfo($order->post_number, $post_type['type']);
            if (!empty($post_info['list'])) {
                $order->post_info = $post_info['list'];
                $order->post_query_time = time();
                $order->save();
                $post_type['exp_phone']=$post_info['expPhone'];
                $post_type['name']=$post_info['expName'];
                $post_type['logo']=$post_info['logo'];
                $post_type->save();
            }
        }
        $data['shipping']=$post_type->toArray();
        $data['order']=Order::where($w)->append(['status_val'])->visible(['post_number', 'post_info', 'status', 'status_val', 'address_info', 'pay_time', 'create_time'])->find()->toArray();
        return self::success($data);
    }

    /**确认收货
     * @param Request $request
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function confirm(Request $request){
        $params=$this->postValidate([
            'order_id|订单号'=>'require'
        ]);
        $w['user_id']=$request->user_id;
        $w['id']=$params['order_id'];
        $order=Order::where($w)->find();
        if(empty($order)||$order->status!=3) return self::error('订单异常');
        $order->status=4;
        $order->save();
        return self::success('');
    }

    /**订单取消
     * @param Request $request
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function orderCancel(Request $request)
    {
        $input = $this->paramsValidate([
            'order_id|订单号' => 'require|integer|length:18'
        ]);
        $w['user_id'] = $request->user_id;
        $w['id'] = $input['order_id'];
        $order = Order::where($w)->find();
        if (!$order) return self::error('订单号非法');
        if ($order['status'] != 2) return self::error('订单状态异常');
        $order->status = 5;
        $order->save();
        return self::success('');
    }
}
