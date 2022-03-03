<?php
declare (strict_types = 1);

namespace app\common\model;

use app\BaseModel;
use mytools\resourcesave\ResourceManager;
use think\Model;

/**
 * @mixin think\Model
 */
class User extends BaseModel
{
    public function getTypeValAttr($v,$d){
        return Type::where('id',$d['idtype'])->value('name');
    }

    //
    public function getHeadImageAttr($v)
    {
        return ResourceManager::staticResource($v);
    }

    public function setHeadImageAttr($v)
    {
        return ResourceManager::net2Path($v);
    }
}
