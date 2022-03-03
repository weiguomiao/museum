<?php
declare (strict_types = 1);

namespace app\api\controller;

use app\BaseController;
use app\common\logic\PayLogic;
use app\common\model\ActivityLog;
use app\common\model\BookLog;
use app\common\model\Card;
use app\common\model\Order;
use app\common\model\PayLog;
use app\common\model\Recharge;
use app\common\model\RechargeOrder;
use app\common\model\User;
use app\common\model\UserAddress;
use app\common\model\UserBalanceLog;
use app\Request;
use mytools\lib\ToolBag;
use mytools\lib\ValidateTool;

class UserController extends BaseController
{
    /**用户中心
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function userCenter(Request $request){
        $data['user_info']=User::where('id', $request->user_id)->visible(['nickname','head_image','balance'])->find();
        $data['wait_pay'] = Order::where('status', 2)->where('user_id', $request->user_id)->count();
        $data['wait_received'] = Order::where('status', 1)->where('user_id', $request->user_id)->count();
        $data['wait_send'] = Order::where('status', 3)->where('user_id', $request->user_id)->count();
        return self::success($data);
    }

    /**余额变动记录
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DbException
     */
    public function balanceLog(Request $request){
        $data = UserBalanceLog::where('user_id', $request->user_id)->append(['type_val','cashier_val'])->order('create_time','desc')->paginate(10, false)->toArray();
        return self::success($data);
    }

    /**消费记录
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DbException
     */
    public function buyLog(Request $request){
        $list=PayLog::where('user_id',$request->user_id)->where('status',1)->append(['mode_val'])->order('create_time','desc')->paginate(10,false);
        return self::success($list);
    }

    /**预约记录
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DbException
     */
    public function bookLog(Request $request){
        $list=BookLog::where('user_id',$request->user_id)->order('time','desc')->append(['status_val','real_name','ven_val'])->paginate(10,false);
        return self::success($list);
    }

    /**报名记录
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DbException
     */
    public function actLog(Request $request){
        $list=ActivityLog::where('user_id',$request->user_id)->order('create_time','desc')->append(['activity','status_val'])->visible(['id','activity','name','mobile','status','create_time'])->paginate(10,false);
        return self::success($list);
    }

    /**
     * 获取用户收货地址
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserAddress(Request $request){
        return self::success(UserAddress::where('user_id', $request->user_id)->order('is_default asc,id desc')->visible(['id', 'name', 'tel', 'area', 'address', 'is_default'])->select());
    }

    /**
     * 保存修改收件地址
     * @param Request $request
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function saveUserAddress(Request $request)
    {
        $input = $this->paramsValidate([
            'id' => 'integer',
            'name|收件人姓名' => 'require',
            'tel|手机号码' => 'require|mobile',
            'area|收件区域' => 'require',
            'address|详细地址' => 'require',
            'is_default|是否默认' => 'require|in:1,2'
        ]);
        $input['user_id'] = $request->user_id;
        $unique_val = md5($input['name'] . $input['tel'] . $input['area'] . $input['address']);
        if (empty($input['id'])) {
            if (UserAddress::where([['user_id', '=',$request->user_id], ['unique_val', '=', $unique_val]])->find()) {
                return self::error('当前地址信息已存在，请勿重复添加！');
            }
        } else {
            if (UserAddress::where([['user_id', '=', $request->user_id], ['unique_val', '=', $unique_val], ['id', '<>', $input['id']]])->find()) {
                return self::error('当前地址信息已存在，请勿重复添加！');
            }
        }
        $input['unique_val'] = $unique_val;
        if ($input['is_default'] == 1) {
            UserAddress::where([['id', '<>', $input['id']], ['user_id', '=',$request->user_id]])->update(['is_default' => 2]);//将其他地址修改为非默认
        }
        $userAddress = UserAddress::saveData($input);
        return self::success($userAddress);
    }

    /**
     * 地址详情
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addressInfo(Request $request){
        $id=$this->request->post('id');
        if(empty($id)) return self::error('参数错误！');
        $addrInfo=UserAddress::where('user_id',$request->user_id)->where('id',$id)->find();
        return self::success($addrInfo);
    }

    /**
     * 删除收货地址
     * @param Request $request
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function delUserAddress(Request $request)
    {
        $input = $this->paramsValidate([
            'id' => 'require|integer'
        ]);
        $re = UserAddress::deleteData($input['id'], ['user_id' => $request->user_id]);
        if (!$re) return self::error('删除失败');
        return self::success('');
    }

    /**展示充值列表
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function rechargeList(){
        $list=Recharge::select();
        return self::success($list);
    }

    /**微信充值订单
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function recharge(){
        $params=$this->postValidate([
            'type|类型'=>'require|in:1,2',//1微信直充,2微信间充
            'money|金额'=>'require|float',
            'recharge_id'=>'',
        ]);
        if (!ValidateTool::isMoney($params['money'])) {
            return self::error('请输入正确金额！');
        }
        switch ($params['type']){
            case 1:
                $order=RechargeOrder::create([
                    'id'=>ToolBag::ymdUniqueId(),
                    'money'=>$params['money'],
                    'user_id'=>$this->request->user_id,
                    'remark'=>'单笔直接充值',
                    'type'=>1
                ]);
                break;
            case 2:
                if(empty($params['recharge_id'])) return self::error('参数错误');
                $recharge=Recharge::where('id',$params['recharge_id'])->find();
                $order=RechargeOrder::create([
                    'id'=>ToolBag::ymdUniqueId(),
                    'money'=>$recharge['recharge'],
                    'user_id'=>$this->request->user_id,
                    'remark'=>'充值赠送：'.$recharge['name'],
                    'extend'=>$recharge['get_money'],
                    'type'=>2
                ]);
                break;
            default:
                return self::error('type参数错误！');
        }
        return self::success(['order_id'=>$order->id,'total_price'=>$order->money]);
    }

    /**卡充值
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function cardRecharge(){
        $params=$this->postValidate([
            'card_no|卡号'=>'require|length:12,13',
            'password|密码'=>'require|length:6'
        ]);
        $card=Card::where('card_no',$params['card_no'])->where('status',1)->find();
        if(empty($card)) return self::error('充值卡账号错误！');
        if($card->status==2) return self::error('该卡已被使用，充值失败！');
        if($card->is_act==2) return self::error('该卡未激活，充值失败！');
        if($card->is_act==5) return self::error('该卡已失效，充值失败！');
        if($card->is_act==3) return self::error('该卡已过期，充值失败！');
        if($card->num>8){
            $card->is_act=4;
            $card->save();
            return self::error('密码错误次数过多，已被锁定！');
        }
        if($card->password!=$params['password']){
            $card->num=['inc',1];
            $card->save();
            return self::error('密码已错误输入'.$card->num.'次！');
        }
        if($card->getData('expire_time')<time()) return self::error('该卡已过期，充值失败！');
        $order=RechargeOrder::create([
            'id'=>ToolBag::ymdUniqueId(),
            'money'=>$card->money,
            'user_id'=>$this->request->user_id,
            'remark'=>'卡充值：卡号'.$card->card_no,
            'type'=>3,
            'status'=>1
        ]);
        $card->status=2;
        $card->user_id=$this->request->user_id;
        $card->save();
        $user=User::find($this->request->user_id);
        $user->balance=['inc',$card['money']];
        $user->save();
        PayLogic::balanceLog($order->money,3,$this->request->user_id,1,'卡充值：卡号'.$card->card_no,$card->card_no);
        return self::success('充值成功');
    }

}
