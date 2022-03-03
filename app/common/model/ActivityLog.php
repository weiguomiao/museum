<?php
declare (strict_types = 1);

namespace app\common\model;

use think\Model;

/**
 * @mixin think\Model
 */
class ActivityLog extends Model
{
    //获取活动标题
    public function getActivityAttr($v,$d){
        return Activity::where('id',$d['act_id'])->visible(['title'])->find();
    }

    //获取状态值
    public function getStatusValAttr($v,$d){
        $status=[1=>'报名成功，待签到',2=>'签到成功',3=>'已失效'];
        return $status[$d['status']];
    }
}
