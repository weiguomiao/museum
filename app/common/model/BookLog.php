<?php
declare (strict_types = 1);

namespace app\common\model;

use app\BaseModel;
use think\Model;

/**
 * @mixin think\Model
 */
class BookLog extends BaseModel
{

    //获取状态值
    public function getStatusValAttr($v,$d){
        $status=[1=>'预约成功，待签到',2=>'签到成功',3=>'已失效'];
        return $status[$d['status']];
    }

    public function getRealNameAttr($v,$d){
        return User::where('id',$d['user_id'])->value('real_name');
    }

    public function getVenValAttr($v,$d){
        $ven_id=BookPt::where('id',$d['book_id'])->value('ven_id');
        return Venue::find($ven_id)['name'];
    }

    public function getIdCardAttr($v,$d){
        return User::where('id',$d['user_id'])->value('idCard');
    }
}
