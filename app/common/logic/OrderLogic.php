<?php


namespace app\common\logic;


use app\common\model\Card;
use app\common\model\Goods;
use app\common\model\GoodsDetail;
use app\common\model\GoodsSpec;
use app\common\model\Top;
use app\common\model\User;
use app\common\service\ConfigService;
use think\db\exception\DataNotFoundException;

class OrderLogic
{

    /**
     * 商品是否上架验证
     * @param $cart
     * @return string
     * @throws DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function isTop($cart){
        foreach ($cart as $v){
            $goodsInfo=GoodsDetail::where('id',$v['id'])->where('status',1)->find();
            if(empty($v['spec_id'])) return '商品规格不能为空';
            if(empty($goodsInfo)) return '您选的商品已下架';
            $spec=GoodsSpec::where('spec_id',$v['spec_id'])->find();
            if(empty($spec)) return '您选的商品规格已下架，请重新添加购物车！';
        }
    }


    /**计算商品总价
     * @param $cart
     * @return float|int|mixed
     * @throws DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function comTotal($cart)
    {
        $total_price=0.00;
        $post['postage']=ConfigService::getValue('postage');
        $post['extend']=ConfigService::getValue('overpost');
        $ship=$post['postage'];
        foreach ($cart as $k=>$v){
            $goodsInfo=GoodsDetail::where('id',$v['id'])->where('status',1)->find();
            $spec=GoodsSpec::where('spec_id',$v['spec_id'])->find();
            $total_price+=round($v['num']*$spec['spec_price'],2);
            if($goodsInfo->is_free_shipping==1){
                $ship=0.00;
            }
        }
        if($total_price>$post['extend']){
            $ship=0.00;
        }
        return [
            'ship'=>round($ship,2),
            'total_price'=>round($total_price,2),
            'total_amount'=>round(($total_price+$ship),2)
        ];
    }

    public static function makeCode()
    {
        //获取时间戳到毫秒
        list($msec, $sec) = explode(' ', microtime());
        $msectime = sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
        $time = $msectimes = substr($msectime, 0, 13);

        //组合出一个十位数券码
        $time = substr($time, -6, 6);
        $rand = mt_rand(100000, 999999);
        //如果存在当前券码，重新生成一个
        $verify = Card::where('card_no', $rand . $time)->find();
        if ($verify) {
            self::makeCode();
        }
        return $rand . $time;
    }

}