<?php
declare (strict_types = 1);

namespace app\common\model;

use app\BaseModel;
use think\Model;

/**
 * @mixin think\Model
 */
class Card extends BaseModel
{
    //类型转换
    protected $type = [
        'expire_time'=>'timestamp:Y-m-d',
    ];

    public function getIdAttr($v)
    {
        return (string)$v;
    }

    public function getStatusValAttr($v,$d){
        $vale=[1=>'未使用',2=>'已使用'];
        return $vale[$d['status']];
    }

    public function getTypeNameAttr($v,$d){
        return CardType::where('id',$d['type'])->value('name');
    }

    public function getUsernameAttr($v,$d){
        return User::where('id',$d['user_id'])->value('nickname');
    }

    //2待激活 1有效 3已过期 4锁定 5失效（作废）
    public function getStatusTextAttr($v,$d){
        $val=[1=>'有效',2=>'待激活',3=>'已过期',4=>'锁定',5=>'失效'];
        return $val[$d['is_act']];
    }
}
