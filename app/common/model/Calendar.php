<?php
declare (strict_types = 1);

namespace app\common\model;

use app\BaseModel;
use think\Model;

/**
 * @mixin think\Model
 */
class Calendar extends BaseModel
{
    //
    public function getStatusValAttr($v,$d){
        $status=[1=>'开启',2=>'关闭'];
        return $status[$d['status']];
    }
}
