<?php
declare (strict_types = 1);

namespace app\api\controller;

use app\BaseController;
use app\common\logic\ActivityLogic;
use app\common\model\Activity;
use app\common\model\ActivityLog;
use app\common\model\BookLog;
use app\common\model\BookPt;
use app\common\model\Type;
use app\common\model\User;
use app\common\model\Venue;
use app\common\service\ConfigService;
use mytools\lib\ValidateTool;
use think\Request;

class ActivityController extends BaseController
{
    /**活动展示
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function actShow(){
        $id=$this->request->post('id');
        if(empty($id)) return self::error('参数错误！');
        $list=Activity::where('id',$id)->visible(['id','title','images','desc','status'])->find()->toArray();
        $list['activityLog']=ActivityLog::where('act_id',$id)->where('user_id',$this->request->user_id)->visible(['name','mobile','status'])->find();
        return self::success($list);
    }

    /**活动报名
     * @param Request $request
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function actSign(Request $request){
        $params=$this->postValidate([
            'name|姓名'=>'require',
            'mobile|手机号'=>'require|mobile',
            'act_id|活动ID'=>'require'
        ]);
        $activity=Activity::where('id',$params['act_id'])->find();
        if($activity->status!=1) return self::error('该活动已经停止，报名失败！');
        if($activity->getData('sign_start_time')<time()) return self::error('报名时间已经截止了哦！');
        $actLog=ActivityLog::where('user_id',$request->user_id)->where('act_id',$params['act_id'])->find();
        if(!empty($actLog)) return self::error('你已经报名了，不能再报名哦！');
        if(($activity->all_num-$activity->num)<1) return self::error('活动报名人数已经满了哦！');
        $activity->num=['inc',1];
        $activity->save();
        ActivityLog::create([
            'user_id'=>$request->user_id,
            'act_id'=>$params['act_id'],
            'name'=>$params['name'],
            'mobile'=>$params['mobile']
        ]);
        return self::success('报名成功!');
    }

    /**预约平台展示
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function bookPtShow(){
        $list['name']=Venue::select();
        $beginToday=strtotime(date('Y-m-d'));
        $list['museum']=BookPt::where('time','=',$beginToday)->where('ven_id',1)->append(['surplus'])->find();
        $list['library']=BookPt::where('time','=',$beginToday)->where('ven_id',2)->append(['surplus'])->find();
        $list['idType']=Type::select();
        $list['userInfo']=User::where('id',$this->request->user_id)->visible(['real_name','idtype','idCard','mobile'])->find();
        return self::success($list);
    }

    /**
     * 预约平台提交
     * @param Request $request
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DbException
     */
    public function submitBook(Request $request){
        $params=$this->postValidate([
            'real_name|真实姓名'=>'require',
            'idtype|证件类型'=>'require',
            'idCard|证件号码'=>'require',
            'mobile|手机号码'=>'require|mobile'
        ]);
        if($params['idtype']==1){
            if (!ValidateTool::isIdcard($params['idCard'])) {
                return self::error('请输入正确身份证号码');
            }
        }
        $user=User::where('id',$request->user_id)->update([
            'real_name'=>$params['real_name'],
            'idtype'=>$params['idtype'],
            'idCard'=>$params['idCard'],
            'mobile'=>$params['mobile']
        ]);
        return self::success($user);
    }

    /**预约展示
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function bookShow(Request $request){
        $venue=$this->request->post('ven_id');
        if(empty($venue)) return self::error('参数为空！');
        $time=strtotime(date('Y-m-d'));
        $list['time']=BookPt::where('time','>=',$time)->where('ven_id',$venue)->append(['surplus','date_val','week_val','year_val'])
            ->order('time','asc')
            ->limit(0,7)
            ->select()->toArray();
        foreach ($list['time'] as $k=>$v){
            //判断用户上午是否预约 1表示已预约 2表示未预约
            $morn=BookLog::where(['user_id'=>$request->user_id,'book_id'=>$v['id'],'extend'=>1])->find();
            if(!empty($morn)) $list['time'][$k]['morn']=1;
            else $list['time'][$k]['morn']=2;
            //判断用户下午是否预约 1表示已预约 2表示未预约
            $after=BookLog::where(['user_id'=>$request->user_id,'book_id'=>$v['id'],'extend'=>2])->find();
            if(!empty($after)) $list['time'][$k]['after']=1;
            else $list['time'][$k]['after']=2;
        }
        if($venue==1){
            $list['morn_time']=ConfigService::getValue('muse_morn_time');
            $list['after_time']=ConfigService::getValue('muse_after_time');
        }else{
            $list['morn_time']=ConfigService::getValue('book_morn_time');
            $list['after_time']=ConfigService::getValue('book_after_time');
        }
        return self::success($list);
    }

    /**报名预约
     * @param Request $request
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addBookPt(Request $request){
        $params=$this->postValidate([
            'book_id|预约ID'=>'require|number',
            'num|预约人数'=>'require|max:5',
            'book_time|预约时间'=>'require',
            'extend'=>'require|in:1,2',//1上午，2下午
        ]);
        $hour=date('H',time());
        $time=strtotime(date('Y-m-d'));
        $bookLog=BookLog::where(['book_id'=>$params['book_id'],'user_id'=>$request->user_id,'extend'=>$params['extend']])->find();
        if(!empty($bookLog)) return self::error('你已经预约过了，不能再预约了哦！');
        $bookPt=BookPt::where('id',$params['book_id'])->append(['surplus'])->find();
        if(empty($bookPt)) return self::error('数据异常，预约失败！');
        if($bookPt->status!=1) return self::error('该时间闭馆，预约失败！');
        if($bookPt->surplus<1) return self::error('预约人数已满，预约失败！');
        if($bookPt->ven_id==1&&$time==$bookPt->time){//博物馆9:30-16:00
            if($params['extend']==1&&$hour>12){
                return self::error('上午已经过了哦，不能预约哦！');
            }
            if($params['extend']==2&&$hour>16){
                return self::error('下午已经过了哦，不能预约哦！');
            }
        }
        if($bookPt->ven_id==2&&$time==$bookPt->time){//图书馆9:30-17:30
            if($params['extend']==1&&$hour>12){
                return self::error('上午已经过了哦，不能预约哦！');
            }
            if($params['extend']==2&&$hour>17){
                return self::error('下午已经过了哦，不能预约哦！');
            }
        }
        BookLog::create([
            'user_id'=>$request->user_id,
            'book_id'=>$params['book_id'],
            'num'=>$params['num'],
            'book_time'=>$params['book_time'],
            'extend'=>$params['extend'],
            'time'=>$bookPt->time,
            'ven_id'=>$bookPt->ven_id
        ]);
        $bookPt->book_num=['inc',$params['num']];
        $bookPt->save();
        return self::success('预约成功！');
    }

    /**博物馆预约详情
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function bookLogInfo(Request $request){
        return self::success(ActivityLogic::bookInfo($request->user_id,1));
    }

    /**图书馆预约详情
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function bookInfo(Request $request){
        return self::success(ActivityLogic::bookInfo($request->user_id,2));
    }

    /**活动报名详情
     * @param Request $request
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function activityInfo(Request $request){
        $input=$this->paramsValidate([
            'id'=>'require'
        ]);
        $actLog=ActivityLog::where('user_id',$request->user_id)->where('act_id',$input['id'])->append(['activity','status_val'])->find();
        return self::success($actLog);
    }
}
