<?php


namespace app\common\logic;


use app\common\model\BookLog;

class ActivityLogic
{
    public static function bookInfo($uid,$ven){
        $beginToday=strtotime(date('Y-m-d'));
        $hour=date('H',time());
        if($hour<12){
            $bookLog=BookLog::where(['time'=>$beginToday,'user_id'=>$uid,'extend'=>1,'ven_id'=>$ven])->append(['status_val','real_name','ven_val','id_card'])->find();
        }else{
            $bookLog=BookLog::where(['time'=>$beginToday,'user_id'=>$uid,'extend'=>2,'ven_id'=>$ven])->append(['status_val','real_name','ven_val','id_card'])->find();
        }
        return $bookLog;
    }

}