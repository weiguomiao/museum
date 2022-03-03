<?php
declare (strict_types = 1);

namespace app\api\controller;

use app\BaseController;
use app\common\logic\PayLogic;
use app\common\logic\SaomaLogic;
use app\common\model\Activity;
use app\common\model\ActivityLog;
use app\common\model\BookLog;
use app\common\model\BookPt;
use app\common\model\PayLog;
use app\common\model\PayRecord;
use app\common\model\Qrcode;
use app\common\model\User;
use mytools\lib\Openssl;
use mytools\lib\ToolBag;
use mytools\lib\ValidateTool;
use think\Request;

class SaomaController extends BaseController
{
    /**扫码支付
     * @param Request $request
     * @return \think\response\Json
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function smPay(Request $request){
        $params=$this->paramsValidate([
            'money|金额'=>'require|float|gt:0',
            'type|支付方式'=>'require|in:1,2',//1微信支付，2余额支付
            'qrcode_id|二维码ID'=>'require'
        ]);
        $userInfo=User::where('id',$request->user_id)->find();
        if(empty($userInfo)) return self::error('用户信息错误！');
        $payid=ToolBag::ymdUniqueId();
        $qrcode=Qrcode::where('id',Openssl::decrypt($params['qrcode_id']))->where('status',1)->find();
        if(empty($qrcode)) return self::error('二维码不可用！');
        if (!ValidateTool::isMoney($params['money'])) {
            return self::error('请输入正确金额！');
        }
        $paylog=PayLog::create([
            'id'=>$payid,
            'order_id'=>$payid,
            'type'=>$params['type'],
            'user_id'=>$request->user_id,
            'mode'=>2,
            'money'=>$params['money'],
            'extend'=>$qrcode->id
        ]);
        if(!$paylog) return self::error('订单创建异常！');
        switch ($params['type']){
            case 1:
                $jsdk = PayLogic::getwechatjsdk($userInfo->openid, $params['money'], $payid);
                $data=[
                    'jsdk'=>$jsdk,
                    'order_id'=>$payid
                ];
                return self::success($data);
                break;
            case 2:
                $pay=self::balancePay($paylog);
                if(empty($pay)) return self::error('余额不足！');
                return self::success($pay);
        }
    }

    /**线下扫码余额支付
     * @param $paylog
     * @return string
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function balancePay($paylog){
        $user = User::find($paylog['user_id']);
        if($user->balance<$paylog['money']) return '';
        $user->balance = ['dec', $paylog['money']];
        $user->save();
        $paylog->status=1;
        $paylog->save();
        PayLogic::balanceLog($paylog->money,4,$user->id,2,'订单号：'.$paylog->id,$paylog->id);
        PayLogic::payRecord($paylog);//生成支付记录
        return '支付成功';
    }

    /**线下收款记录
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DbException
     */
    public function payRecordLog(Request $request){
        $time=$this->request->post('time');
        $w=[];
        if(!empty($time)){
            $startTime = strtotime($time[0]);
            $endTime = strtotime($time[1]);
            $w[] = ['create_time', 'between', [$startTime, $endTime + 86399]];
        }
        $list=PayRecord::where($w)->where('to_user_id',$request->user_id)
            ->append(['type_val','nickname'])
            ->order('create_time','desc')
            ->paginate(10,false);
        return self::success($list);
    }


    /**扫码签到
     * @param Request $request
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function sign(Request $request){
        $params=$this->paramsValidate([
            'mode'=>'require|in:1,2,3',//1活动，2博物馆预约，3图书馆预约
            'id'=>'requireIf:mode,1'
        ]);
        $time=time();
        $beginToday=strtotime(date('Y-m-d'));
        $hour=date('H',$time);
        switch ($params['mode']){
            case 1:
                if(empty($params['id'])) return self::error('活动ID不能为空！');
                $activity=Activity::where('id',$params['id'])->where('status',1)->find();
                if(empty($activity)) return self::error('该活动已经结束啦！');
                $activityLog=ActivityLog::where('user_id',$request->user_id)->where('act_id',$params['id'])->append(['activity','status_val'])->find();
                if(empty($activityLog)) return self::error('您没有报名该活动，签到失败！');
                if($activityLog->status==2) return self::error('您已经签到了，不需要重新签到！');
                if($activityLog->status==3) return self::error('您报名的活动已经过期了，需要您重新报名！');
                if($activityLog->status==1){
                    if($time>=$activity->getData('sign_start_time')&&$time<=$activity->getData('sign_end_time')){
                        $activityLog->status=2;
                        $activityLog->save();
                        return self::success('签到成功！');
                    }
                    if($time<$activity->getData('sign_start_time')){
                        return self::error('活动签到还没有开始哦！');
                    }
                    if($time>$activity->getData('sign_end_time')){
                        $activityLog->status=3;
                        $activityLog->save();
                        return self::error('您报名的活动签到时间已经过期了，需要您重新报名！');
                    }
                }
                break;
            case 2:
                if($hour<12){
                    $bookLog=BookLog::where(['time'=>$beginToday,'user_id'=>$request->user_id,'extend'=>1,'ven_id'=>1])->append(['status_val','real_name','ven_val','id_card'])->find();
                }elseif($hour<16){
                    $bookLog=BookLog::where(['time'=>$beginToday,'user_id'=>$request->user_id,'extend'=>2,'ven_id'=>1])->append(['status_val','real_name','ven_val','id_card'])->find();
                }
                if(empty($bookLog)) return self::error('您的预约不在签到时间内！');
                if($bookLog->status==1){
                    $bookLog->status=2;
                    $bookLog->save();
                    return self::success($bookLog);
                }
                if($bookLog->status==2){
                    return self::error('已经签到了，不需要重复签到哦！');
                }
                if($bookLog->status==3){
                    return self::error('预约失效，请预约下次哦！');
                }
                break;
            case 3:
                if($hour<12){
                    $bookLog=BookLog::where(['time'=>$beginToday,'user_id'=>$request->user_id,'extend'=>1,'ven_id'=>2])->append(['status_val','real_name','ven_val','id_card'])->find();
                }elseif($hour<16){
                    $bookLog=BookLog::where(['time'=>$beginToday,'user_id'=>$request->user_id,'extend'=>2,'ven_id'=>2])->append(['status_val','real_name','ven_val','id_card'])->find();
                }
                if(empty($bookLog)) return self::error('您的预约不在签到时间内！');
                if($bookLog->status==1){
                    $bookLog->status=2;
                    $bookLog->save();
                    return self::success($bookLog);
                }
                if($bookLog->status==2){
                    return self::error('已经签到了，不需要重复签到哦！');
                }
                if($bookLog->status==3){
                    return self::error('预约失效，请预约下次哦！');
                }
                break;
        }
    }

}
