<?php
declare (strict_types = 1);

namespace app\common\model;

use app\BaseModel;
use app\common\getAttr\ImageAttr;
use mytools\resourcesave\ResourceManager;
use think\Model;

/**
 * @mixin think\Model
 */
class Goods extends BaseModel
{
    use ImageAttr;

    public function getVoiceAttr($v)
    {
        return ResourceManager::staticResource($v);
    }

    public function setVoiceAttr($v)
    {
        return ResourceManager::net2Path($v);
    }


}
