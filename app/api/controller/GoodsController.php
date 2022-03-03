<?php
declare (strict_types = 1);

namespace app\api\controller;

use app\BaseController;
use app\common\model\Banner;
use app\common\model\Classify;
use app\common\model\Goods;
use app\common\model\GoodsDetail;
use app\common\model\User;
use app\common\service\ConfigService;
use think\Request;

class GoodsController extends BaseController
{
    /**
     * 藏品详情
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function goodsInfo(){
        $params=$this->paramsValidate([
            'id'=>'require'
        ]);
        $goodsInfo=Goods::where('id',$params['id'])->find();
        return self::success($goodsInfo);
    }

    /**商城首页
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index(){
        $list['banner']=Banner::where('status',1)->select();
        $list['classify']=Classify::where('status',1)->select();
        $list['goods']=GoodsDetail::where('status',1)
            ->visible(['id','name','image','price'])
            ->order('sales','desc')
            ->order('top','desc')
            ->paginate(10,false);
        return self::success($list);
    }

    /**商品列表
     * @return \think\response\Json
     * @throws \think\db\exception\DbException
     */
    public function goodsList(){
        $list=GoodsDetail::where('status',1)
            ->visible(['id','name','image','price'])
            ->order('sales','desc')
            ->order('top','desc')
            ->paginate(10,false);
        return self::success($list);
    }

    /**分类列表
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function cidList(){
        $list=Classify::where('status',1)->visible(['id','title'])->select();
        return self::success($list);
    }

    /**分类
     * @return \think\response\Json
     * @throws \think\db\exception\DbException
     */
    public function classify(){
        $cid=$this->request->post('cid');
        $key=$this->request->post('key');
        $w=[];
        if(!empty($key)){
            $w[]=['name','like','%'.$key.'%'];
        }
        if(!empty($cid)){
            $w[]=['cid','=',$cid];
        }
        $goodsList=GoodsDetail::where($w)
            ->where('status',1)
            ->visible(['id','name','image','price'])
            ->order('sales','desc')
            ->order('top','desc')
            ->paginate(10,false);
        return self::success($goodsList);
    }

    /**
     * 商品详情
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function goodsDetail(){
        $params=$this->paramsValidate([
            'id'=>'require'
        ]);
        $goodsInfo=GoodsDetail::where('id',$params['id'])
            ->append(['type','spec'])
            ->find()->toArray();
        $ship['postage']=ConfigService::getValue('postage');
        $ship['extend']=ConfigService::getValue('overpost');
        $viewData=[
            'goodsInfo'=>$goodsInfo,
            'ship'=>$ship,
        ];
        return self::success($viewData);
    }
}
