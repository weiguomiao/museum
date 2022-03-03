<?php
declare (strict_types = 1);

namespace app\common\model;

use app\BaseModel;
use think\Model;

/**
 * @mixin think\Model
 */
class PayRecord extends BaseModel
{
    //1微信支付，2余额支付
    public function getTypeValAttr($v,$d){
        $val=[1=>'微信支付',2=>'余额支付'];
        return $val[$d['type']];
    }

    //
    public function getNicknameAttr($v,$d){
        return User::where('id',$d['user_id'])->value('nickname');
    }
}
