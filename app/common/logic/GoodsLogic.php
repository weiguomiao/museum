<?php


namespace app\common\logic;


use app\common\model\GoodsDetail;
use app\common\model\GoodsSpec;

class GoodsLogic
{
    public static function specPrice($goods_id){
        $skuList=GoodsSpec::where('goods_id',$goods_id)->select();
        $goods=GoodsDetail::where('id',$goods_id)->find();
        $arr=[];
        foreach ($skuList as $v){
            $arr[]=$v['spec_price'];
        }
        if(!empty($arr)){
            $goods->price=min($arr);
            $goods->status=1;
            $goods->save();
        }else{
            $goods->price=0;
            $goods->status=2;
            $goods->save();
        }
    }

    public static function specCheck($goods_id){
        $sku_num=GoodsSpec::where('goods_id',$goods_id)->count();
        if($sku_num==0){
            $goods=GoodsDetail::where('id',$goods_id)->find();
            $goods->status=2;
            $goods->save();
        }
    }

}