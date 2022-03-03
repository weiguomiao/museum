<?php
declare (strict_types=1);

namespace app\admin\controller;

use app\common\model\Order;
use app\common\model\PostType;
use app\common\model\User;
use app\common\service\ApiClientService;
use mytools\office\MyExcel;
use think\facade\Log;

/**
 * 订单管理
 * Class OrderController
 * @package app\admin\controller
 */
class OrderController extends AdminBaseController
{
    /**
     * @authName (查看订单列表)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (21)
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DbException
     */
    public function orderList()
    {
        $params = $this->paramsValidate([
            'keyword' => '',
            'status' => 'number',
            'time'=>''
        ]);
        $w = Order::makeWhere($params, [
            ['id|address_info', 'like', 'keyword'],
            ['status','=']
        ]);
        if (!empty($params['time'])) {
            $time=$params['time'];
            $startTime = strtotime($time[0]);
            $endTime = strtotime($time[1]);
            $w[] = ['create_time', 'between', [$startTime, $endTime + 86399]];
        }
        if(empty($params['status'])){
            $w[]= ['status','not in',[2,5]];
        }
        $data = Order::where($w)
            ->append(['orderGoods','statusVal','post_name'])
            ->order('create_time', 'desc')
            ->paginate($this->default_limit, false)
            ->toArray();
        return self::success($data);
    }


    /**
     * @authName (发货添加修改快递单号)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (22)
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addPost()
    {
        $params = $this->paramsValidate([
            'id|订单ID' => 'require|number',
            'post_id|物流公司' => 'require|number',
            'post_number|物流单号' => 'require',
        ]);
        $order = Order::where('id', $params['id'])->find();
        if (empty($order)) return self::error('订单查询为空！');
        $order->status = 3;
        $order->post_id = $params['post_id'];
        $order->post_number = $params['post_number'];
        $order->post_send_time = time();
        $order->save();
        return self::success('操作成功');
    }

    /**查询物流
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function postInfo(){
        $input = $this->paramsValidate([
            'id|订单号' => 'require|integer|length:18'
        ]);
        $w['id'] = $input['id'];
        $order = Order::where($w)->find();
        if (!$order) return self::error('订单号非法');
        if (!in_array($order['status'], [3, 4])) return self::error('订单未发货');
        $post_type=PostType::where('id',$order->post_id)->find();
        if ($order['status'] == 3 && (time() - $order['post_query_time']) > 900) {
            $post_info = (new ApiClientService())->postInfo($order->post_number, $post_type['type']);
            if (!empty($post_info['list'])) {
                $order->post_info = $post_info['list'];
                $order->post_query_time = time();
                $order->save();
                $post_type['tel']=$post_info['expPhone'];
                $post_type['name']=$post_info['expName'];
                $post_type['logo']=$post_info['logo'];
                $post_type->save();
            }
        }
        $data=Order::where($w)->visible(['post_info'])->find()->toArray();
        return self::success($data);
    }

    /**
     * 导出excel表
     */
    public function export()
    {
        $status = $this->request->get('status');
        $time=$this->request->get('time');
        $w=[];
        if(empty($status)) {
            $status="1,3,4";
            $w[]=['status','in',explode(',',$status)];
        }else{
            $w[]=['status','in',explode(',',$status)];
        }
        $time=explode(',',$time);
        if (isset($time)&&isset($time[0])&&isset($time[1])) {
            $startTime = strtotime($time[0]);
            $endTime = strtotime($time[1]);
            $w[] = ['create_time', 'between', [$startTime, $endTime + 86399]];
        }
        $order = Order::where($w)
            ->withAttr('id', function ($v) {
                return "\t" . $v;
            })
            ->select()
            ->toArray();
        $data=[];
        $status_val=[ 1 => '待发货',2=>'待支付',3=>'待收货',4=>'已完成',5=>'已取消'];
        foreach ($order as $value){
            $data[]=[
                'id'=>$value['id'],
                'name'=>$value['address_info']?$value['address_info']['name']:'',
                'tel'=>$value['address_info']?$value['address_info']['tel']:'',
                'address'=>$value['address_info']?$value['address_info']['area'].$value['address_info']['address']:'',
                'total_amount'=>$value['total_amount'].'元',
                'create_time'=>$value['create_time'],
                'status'=>$status_val[$value['status']],
                'pay_time'=>$value['pay_time']?$value['pay_time']:''
            ];
        }
        $arr = [];
        foreach ($data as $k => $v) {
            $arr[] = array_values($v);
        }
        $excel = [
            'save_name' => '商城订单',
            'table' => [
                // 表格1
                'sheet1' => [
                    // 工作表标题
                    'title' => '商品订单',
                    // 表格标题
                    'table_captain' => '商品订单',
                    // 边框
                    'border' => true,
                    // 字段
                    'field' => [
                        [
                            '订单编号',
                            ['width' => 20]
                        ],
                        [
                            '收货人名称',
                            ['width' => 30]
                        ],
                        [
                            '收货人电话',
                            ['width' => 20]
                        ],
                        [
                            '收货地址',
                            ['width' => 100]
                        ],
                        [
                            '总金额',
                            ['width' => 20]
                        ],
                        [
                            '订单创建时间',
                            ['width' => 40]
                        ],
                        [
                            '订单状态',
                            ['width' => 20]
                        ],
                        [
                            '支付时间',
                            ['width' => 30]
                        ],
                    ],
                    // 数据
                    'content' => $arr,
                ],
            ],

        ];
        $re = (new MyExcel())->create($excel);
        return self::success($re);
    }

    /**
     * 物流公司列表
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function postList()
    {
        $list = PostType::select();
        return self::success($list);
    }
}
