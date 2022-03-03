<?php
declare (strict_types = 1);

namespace app\admin\controller;
use app\common\model\Goods;

/**
 * 藏品管理
 * Class IndexController
 * @package app\admin\controller
 */
class GoodsController extends AdminBaseController
{
    /**
     * 藏品列表
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (14)
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DbException
     */
    public function goodsList(){
        $params=$this->paramsValidate([
            'keyword'=>'',
            'status'=>''
        ]);
        $w=Goods::makeWhere($params,[
           ['id|name','like','keyword']
        ]);
        $status['status']=empty($params['status'])?[1,2]:$params['status'];
        $list=Goods::where($w)
            ->where($status)
            ->order('id','asc')
            ->paginate($this->default_limit,false);
        return self::success($list);
    }

    /**
     * @authName (添加修改藏品)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (15)
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addGoods(){
        $params=$this->paramsValidate([
            'id'=>'',
            'name|藏品名称'=>'require',
            'image|藏品图片'=>'require',
            'voice|音频'=>'',
            'years|年代'=>'require',
            'introduce|藏品解析'=>'require',
            'spec|规格'=>'require'
        ]);
        return self::success(Goods::saveData($params));
    }

    /**
     * 设置藏品状态 1上架，2下架
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setGoodsStatus(){
        $params=$this->paramsValidate([
            'id'=>'require'
        ]);
        return self::success(Goods::saveStatus($params['id']));
    }
}
