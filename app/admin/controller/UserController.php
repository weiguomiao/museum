<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\BaseController;
use app\common\model\ActivityLog;
use app\common\model\BookLog;
use app\common\model\PayRecord;
use app\common\model\Qrcode;
use app\common\model\Recharge;
use app\common\model\RechargeOrder;
use app\common\model\User;
use app\common\model\UserBalanceLog;
use mytools\lib\ToolBag;
use mytools\office\MyExcel;

/**
 * 用户管理
 * Class UserController
 * @package app\admin\controller
 */
class UserController extends AdminBaseController
{
    /**
     * @authName (用户列表)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (40)
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DbException
     */
    public function userList(){
        $input=$this->paramsValidate([
            'status'=>'number',
            'keyword'=>''
        ]);
        $w=User::makeWhere($input,[
            ['id|nickname|real_name|mobile','like','keyword'],
            ['status','=']
        ]);
        $list=User::where($w)->append(['type_val'])->paginate(10,false);
        return self::success($list);
    }

    /**
     * @authName (修改用户状态)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (41)
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function status()
    {
        $input = $this->postValidate([
            'id' => 'require',
        ]);
        $member = User::find($input['id']);
        if (!$member) return self::error('用户id非法');
        $re = User::where('id', $input['id'])->update(['status' => $member->status == 1 ? 2 : 1]);
        return $re ? self::success('') : self::error('操作失败');
    }

    /**
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setLevel(){
        $input = $this->postValidate([
            'id' => 'require',
        ]);
        $member = User::find($input['id']);
        if (!$member) return self::error('用户id非法');
        $re=User::where('id',$input['id'])->update(['level'=>$member->level==1?2:1]);
        return $re ? self::success('') : self::error('操作失败');
    }

    /**
     * @authName (查看用户余额记录)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (42)
     * @return \think\response\Json
     * @throws \think\db\exception\DbException
     */
    public function balanceLog(){
        $uid = $this->request->post('id');
        if(empty($uid)) return self::error('参数错误！');
        $data = UserBalanceLog::where('user_id', $uid)
            ->append(['type_val','cashier_val'])
            ->order('create_time','desc')
            ->paginate(10, false)
            ->toArray();
        return self::success($data);
    }

    /**
     * @authName (查看用户报名活动记录)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (43)
     * @return \think\response\Json
     * @throws \think\db\exception\DbException
     */
    public function actLog(){
        $uid = $this->request->post('id');
        if(empty($uid)) return self::error('参数错误！');
        $data=ActivityLog::where('user_id',$uid)
            ->append(['activity','status_val'])
            ->order('create_time','desc')
            ->paginate($this->default_limit, false)
            ->toArray();
        return self::success($data);
    }

    /**
     * @authName (查看用户预约记录)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (44)
     * @return \think\response\Json
     * @throws \think\db\exception\DbException
     */
    public function bookLog(){
        $uid = $this->request->post('id');
        if(empty($uid)) return self::error('参数错误！');
        $data=BookLog::where('user_id',$uid)
            ->append(['status_val','ven_val'])
            ->order('create_time','desc')
            ->paginate($this->default_limit, false)
            ->toArray();
        return self::success($data);
    }

    /**
     * @authName (用户充值记录)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (45)
     * @return \think\response\Json
     * @throws \think\db\exception\DbException
     */
    public function rechargeLog(){
        $uid = $this->request->post('id');
        if(empty($uid)) return self::error('参数错误！');
        $data=RechargeOrder::where('status',1)
            ->where('user_id',$uid)->append(['type_val'])
            ->order('create_time','desc')
            ->paginate($this->default_limit, false)
            ->toArray();
        return self::success($data);
    }

    /**
     * @authName (收款二维码列表)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (46)
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DbException
     */
    public function qrcodeList(){
        $input=$this->paramsValidate([
            'keyword'=>''
        ]);
        $w=Qrcode::makeWhere($input,[
            ['user_id','like','keyword']
        ]);
        $list=Qrcode::where($w)->append(['nickname'])->paginate(10,false);
        return self::success($list);
    }

    /**
     * @authName (用户关联二维码)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (47)
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addQrcode(){
        $params=$this->paramsValidate([
            'id'=>'number',
            'title|标题'=>'require',
            'user_id|用户ID'=>'require',
            'status'=>'require|in:1,2'
        ]);
        $user=User::where('id',$params['user_id'])->find();
        if(empty($user)) return self::error('用户ID错误！');
        $re=Qrcode::saveData($params);
        return self::success($re);
    }

    /**修改二维码状态
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setQrcodeStatus(){
        $params=$this->paramsValidate([
            'id'=>'require'
        ]);
        return self::success(Qrcode::saveStatus($params['id']));
    }

    /**
     * @authName (扫码收款记录)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (48)
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DbException
     */
    public function smpayRecord(){
        $params=$this->paramsValidate([
            'id'=>'require',
        ]);
        $list=PayRecord::where('qr_id',$params['id'])->append(['type_val'])->paginate(10,false);
        return self::success($list);
    }

    //收款表格
    public function PayExcel(){
        $id = $this->request->get('id');
        $time=$this->request->get('time');
        $w=[];
        if(empty($id)) return self::error('id不能为空');
        $id=explode(',',$id);
        $w[]=['qr_id','in',$id];
        $time=explode(',',$time);
        if (isset($time)&&isset($time[0])&&isset($time[1])) {
            $startTime = strtotime($time[0]);
            $endTime = strtotime($time[1]);
            $w[] = ['create_time', 'between', [$startTime, $endTime + 86399]];
        }
        $log=PayRecord::where($w)->append(['type_val'])
            ->visible(['user_id','create_time','type_val','to_user_id','money'])
            ->select()
            ->toArray();
        $data=[];
        foreach ($log as $value){
            $data[]=[
                'user_id'=>$value['user_id']??'',
                'create_time'=>$value['create_time']??'',
                'type_val'=>$value['type_val']??'',
                'to_user_id'=>$value['to_user_id']??'',
                'money'=>$value['money']??'',
            ];
        }
        $arr=[];
        foreach ($data as $k => $v) {
            $arr[] = array_values($v);
        }
        $excel = [
            'save_name' => '收款账单',
            'table' => [
                // 表格1
                'sheet1' => [
                    // 工作表标题
                    'title' => '收款列表',
                    // 表格标题
                    'table_captain' => '收款账单',
                    // 边框
                    'border' => true,
                    // 字段
                    'field' => [
                        [
                            '付款用户ID',
                            ['width' => 20]
                        ],
                        [
                            '支付时间',
                            ['width' => 20]
                        ],
                        [
                            '类型',
                            ['width' => 20]
                        ],
                        [
                            '收款人ID',
                            ['width' => 30]
                        ],
                        [
                            '支付金额（单位：元）',
                            ['width' => 30]
                        ],
                    ],
                    // 数据
                    'content' => $arr,
                ],
            ],

        ];
        $re = (new MyExcel())->create($excel);
        return self::success($re);
    }

}
