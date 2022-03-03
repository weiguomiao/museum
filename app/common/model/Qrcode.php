<?php
declare (strict_types = 1);

namespace app\common\model;

use app\BaseModel;
use think\Model;

/**
 * @mixin think\Model
 */
class Qrcode extends BaseModel
{
    //
    public function getNicknameAttr($v,$d){
        return User::where('id',$d['user_id'])->value('nickname');
    }
}
