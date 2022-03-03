<?php

namespace app\common\model;

use app\BaseModel;
use mytools\resourcesave\ResourceManager;

class Config extends BaseModel
{
    protected $pk = 'key';

    // 值获取器
    public function getValueAttr($value, $data)
    {
        switch ($data['type']) {
            case 'image':
                return ResourceManager::staticResource($value);
            default:
                return $value;
        }
    }

    // 值修改器
    public function setValueAttr($value, $data)
    {
        switch ($data['type']) {
            case 'image':
                return ResourceManager::net2Path($value);
            default:
                return $value;
        }
    }
}
