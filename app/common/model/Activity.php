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
class Activity extends BaseModel
{
    // 设置json类型字段
    protected $json = ['images'];
    // 设置JSON数据返回数组
    protected $jsonAssoc = true;

    protected $type = [
        'sign_start_time'    =>  'timestamp:Y-m-d H:i:s',
        'sign_end_time'    =>  'timestamp:Y-m-d H:i:s',
//        'act_time'    =>  'timestamp:Y-m-d H:i:s',
    ];

    public function getImagesAttr($d)
    {
        $re=[];
        if(empty($d)) return $re;
        foreach ($d as $v)
            $re[]=ResourceManager::staticResource($v);
        return $re;
    }

    public function setImagesAttr($d)
    {
        $re=[];
        if(empty($d)) return $re;
        foreach ($d as $v)
            $re[]=ResourceManager::net2Path($v);
        return $re;
    }
}
