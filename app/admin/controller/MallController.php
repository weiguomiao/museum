<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\common\logic\GoodsLogic;
use app\common\model\Classify;
use app\common\model\Goods;
use app\common\model\GoodsDetail;
use app\common\model\GoodsSpec;

/**
 * 商品管理
 * Class MallController
 * @package app\admin\controller
*/
class MallController extends AdminBaseController
{
    /**
     * @authName (查看商品列表)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (16)
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DbException
     */
    public function goodsList(){
        $params=$this->paramsValidate([
            'keyword'=>'',
            'status'=>'number'
        ]);
        $w=Goods::makeWhere($params,[
            ['id|name','like','keyword'],
            ['status','=']
        ]);
        $list=GoodsDetail::where($w)
            ->append(['type','spec'])
            ->order('create_time','desc')
            ->paginate($this->default_limit,false)->toArray();
        return self::success($list);
    }

    /**添加修改商品
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addGoods(){
        $params=$this->paramsValidate([
            'id'=>'number',
            'name|商品名称'=>'require',
            'image|商品logo'=>'require',
            'status|状态'=>'require|in:1,2', //1上架，2下架
            'banner|商品轮播图'=>'require|array',
            'spec|商品规格'=>'require',     //规格值数组:规格值名称spec_id,spec_name,商品价格spec_price
            'cid|商品类型'=>'require|integer|egt:0',
            'desc|商品详情'=>'require',
            'is_free_shipping|是否包邮'=>'require|in:1,2'
        ]);
        $params['price']=$params['spec'][0]['spec_price'];
        foreach ($params['spec'] as $val){
            if($params['price']>$val['spec_price'])
                $params['price']=$val['spec_price'];
        }
        $goods=GoodsDetail::saveData($params);
        foreach ($params['spec'] as $k=>$v){
            if(empty($v['spec_name'])||empty($v['spec_price'])){
                unset($v);
            }else{
                if(empty($v['spec_id'])){
                GoodsSpec::create([
                    'goods_id'=>$goods->id,
                    'spec_name'=>$v['spec_name'],
                    'spec_price'=>$v['spec_price']
                ]);
            }else{
                GoodsSpec::update([
                    'spec_id'=>$v['spec_id'],
                    'goods_id'=>$goods->id,
                    'spec_name'=>$v['spec_name'],
                    'spec_price'=>$v['spec_price']
                ]);
                }
            }
        }
        return self::success('操作成功');
    }

    /**
     * @authName (添加编辑商品)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (17)
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function goodsSave()
    {
        $input = $this->postValidate([
            'id'=>'number',
            'name|商品名称'=>'require',
            'image|商品logo'=>'require',
            'status|状态'=>'require|in:1,2', //1上架，2下架
            'banner|商品轮播图'=>'require|array',
            'cid|商品类型'=>'require|integer|egt:0',
            'desc|商品详情'=>'require',
            'is_free_shipping|是否包邮'=>'require|in:1,2'
        ]);
        $goods=GoodsDetail::saveData($input);
        $sku=GoodsSpec::where('goods_id',$goods->id)->count();
        if($sku==0){
            $goods->status=2;
            $goods->save();
        }
        return self::success('操作成功');
    }

    /**规格列表
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DbException
     */
    public function specList(){
        $params=$this->postValidate([
            'id|商品ID'=>'require'
        ]);
        $list=GoodsSpec::where('goods_id',$params['id'])->paginate(10,false);
        return self::success($list);
    }

    /**添加编辑规格
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setSpec(){
        $params=$this->paramsValidate([
            'spec_id'=>'number',
            'goods_id|商品ID'=>'require|number',
            'spec_name|规格名称'=>'require',
            'spec_price|规格价格'=>'require|gt:0'
        ]);
        GoodsSpec::saveData($params);
        GoodsLogic::specPrice($params['goods_id']);
        return self::success('');
    }

    /**删除规格
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function delSpec(){
        $params=$this->paramsValidate([
            'spec_id'=>'require'
        ]);
        $spec=GoodsSpec::where('spec_id',$params['spec_id'])->find();
        GoodsSpec::deleteData($params['spec_id']);
        GoodsLogic::specPrice($spec->goods_id);
        GoodsLogic::specCheck($spec->goods_id);
        return self::success('删除成功');
    }

    /**
     * 设置商品状态 1上架，2下架
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
        $spec=GoodsSpec::where('goods_id',$params['id'])->count();
        if($spec==0) return self::error('规格暂无，不能上架哦！');
        return self::success(GoodsDetail::saveStatus($params['id']));
    }


    /**商品置顶
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function goodsTop()
    {
        $input = $this->postValidate([
            'id' => 'require',
            'top' => 'require|in:1,2',
        ]);
        $goods = GoodsDetail::find($input['id']);
        if (!$goods) return self::error('商品id非法');
        $re = GoodsDetail::where('id', $input['id'])->update(['top' => $input['top'] == 1 ? time() : 0]);
        return $re ? self::success('') : self::error('操作失败');
    }

    /**
     * @authName (分类列表)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (18)
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DbException
     */
    public function typeList(){
        $params=$this->paramsValidate([
            'status'=>'number|in:1,2'
        ]);
        if($params['status']==1){
            $list=Classify::where('status',1)->select();
        }else{
            $list=Classify::paginate($this->default_limit,false);
        }
        return self::success($list);
    }

    /**
     * @authName (添加编辑分类)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (19)
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addType(){
        $params=$this->paramsValidate([
            'id'=>'',
            'title'=>'require',
            'status'=>'require|in:1,2',
            'image'=>'require'
        ]);
        return self::success(Classify::saveData($params));
    }

    /**
     * @authName (修改分类状态)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (20)
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setTypeStatus(){
        $params=$this->paramsValidate([
            'id'=>'require'
        ]);
        return self::success(Classify::saveStatus($params['id']));
    }
}
