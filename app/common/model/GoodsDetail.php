<?php
declare (strict_types = 1);

namespace app\common\model;

use app\BaseModel;
use app\common\getAttr\ImageAttr;
use mytools\resourcesave\ResourceManager;
use think\Model;
use think\model\concern\SoftDelete;

/**
 * @mixin think\Model
 */
class GoodsDetail extends BaseModel
{
    //上传图片
    use ImageAttr;

    //设置价格浮点型
    protected $type = [
        'price'    =>  'float'
    ];

    // 设置json类型字段
    protected $json = ['banner'];
    // 设置JSON数据返回数组
    protected $jsonAssoc = true;

    public function getBannerAttr($d)
    {
        $re=[];
        if(empty($d)) return $re;
        foreach ($d as $v)
            $re[]=ResourceManager::staticResource($v);
        return $re;
    }

    public function setBannerAttr($d)
    {
        $re=[];
        if(empty($d)) return $re;
        foreach ($d as $v)
            $re[]=ResourceManager::net2Path($v);
        return $re;
    }


    public function getDescAttr($v)
    {
        return str_replace('{url}', config('conf.static_url'), $v);
    }

    public function setDescAttr($v)
    {
        return str_replace(config('conf.static_url'), '{url}', $v);
    }

    /**
     * 获取商品分类名称
     * @param $v
     * @param $d
     * @return mixed
     */
    public function getTypeAttr($v,$d){
        return Classify::where('id',$d['cid'])->value('title');
    }

    /**
     * 获取商品规格
     * @param $v
     * @param $d
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSpecAttr($v,$d){
        return GoodsSpec::where('goods_id',$d['id'])->order('spec_id asc')->select()->toArray();
    }
}
