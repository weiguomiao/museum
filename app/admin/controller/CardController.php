<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\common\logic\OrderLogic;
use app\common\model\Card;
use app\common\model\CardActivationLog;
use app\common\model\CardBatch;
use app\common\model\CardType;
use app\common\model\Recharge;
use app\common\model\User;
use mytools\lib\Openssl;
use mytools\office\MyExcel;

/**
 * 充值卡管理
 * Class CardController
 * @package app\admin\controller
 */
class CardController extends AdminBaseController
{
    /**
     * @authName (展示充值卡)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (30)
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DbException
     */
    public function cardList(){
        $params=$this->postValidate([
            'keyword'=>'',
            'status'=>'in:1,2',
            'time'=>'',
            'is_act'=>'in:1,2,3,4,5',
            'batch'=>'number',
            'listRows'=>'require'
        ]);
        $w=Card::makeWhere($params,[
           ['card_no','like','keyword'],
           ['status','='],
           ['is_act','='],
           ['batch','=']
        ]);
        if (!empty($params['time'])) {
            $time=$params['time'];
            $startTime = strtotime($time[0]);
            $endTime = strtotime($time[1]);
            $w[] = ['create_time', 'between', [$startTime, $endTime + 86399]];
        }
        $list=Card::where($w)->append(['status_val','typeName','username','statusText'])
            ->hidden(['password'])
            ->order('id','desc')
            ->paginate($params['listRows'],false);
        return self::success($list);
    }

    /**生成充值卡
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DbException
     */
//    public function addCard(){
//        $params=$this->postValidate([
//            'number|卡数量'=>'require',
//            'money|金额'=>'require',
//            'type|充值卡类型'=>''
//        ]);
//        $arr=[];
//        for ($num=$params['number'];$num>0;$num--){
//            $arr[]=[
//                'card_no'=>OrderLogic::makeCode(),
//                'password'=>mt_rand(100000,999999),
//                'money'=>$params['money'],
//                'type'=>1,
//                'create_time'=>time()
//            ];
//        }
//        (new Card())->insertAll($arr);
//        return self::success('添加成功');
//    }

    /**
     * @authName (添加卡)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (31)
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DbException
     */
    public function addCardBatch()
    {
        $input = $this->postValidate([
            'type|卡类型' => 'require|number',
            'num|数量' => 'require|number|max:9999',
        ]);
        $input['user_id'] = $this->request->admin_id;
        $cardBatch = CardBatch::order('create_time', 'desc')->value('batch');
        $input['batch'] = empty($cardBatch) ? 100 : $cardBatch + 1;
        CardBatch::create($input);

        //添加卡片
        $data = [];
        $number = 0001;
        for ($i = 1; $i <= $input['num']; $i++) {
            //随机数
            $rand = rand(100000, 999999);
            //序号
            $number = self::handleNumber($number);
            // 随机六位数密码
            $pwd = rand(100000, 999999);
            $data[] = [
                'batch' => $input['batch'],
                'card_no' => $input['batch'] . $rand . $number,
                'type' => $input['type'],
                'password' => $pwd,
                'create_time' => time(),
                'number' => '1' . $number
            ];
            $number++;
        }
        (new Card())->insertAll($data);
        return self::success('成功');
    }

    /**
     * 处理序号
     * @param $number
     * @return string
     */
    public static function handleNumber($number)
    {
        $len = mb_strlen((string)$number);
        if ($len == 3) {
            $number = '0' . $number;
        } elseif ($len == 2) {
            $number = '00' . $number;
        } elseif ($len == 1) {
            $number = '000' . $number;
        }
        return $number;
    }

    /**卡类型
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCardType(){
        $list=CardType::select();
        return self::success($list);
    }

    /**
     * @authName (卡批次列表)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (32)
     * @return \think\response\Json
     * @throws \think\db\exception\DbException
     */
    public function cardBatch(){
        $list=CardBatch::where('')
            ->withAttr('type',function ($v){
            return CardType::where('id',$v)->value('name');
        })
            ->paginate(10,false);
        return self::success($list);
    }

    /**
     * @authName (激活记录)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (33)
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DbException
     */
    public function activationLog(){
        $params=$this->postValidate([
            'batch|批次'=>'require'
        ]);
        $list=CardActivationLog::where('batch',$params['batch'])->paginate(10,false);
        return self::success($list);
    }

    /**
     * @authName (卡编辑)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (34)
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DbException
     */
    public function cardSave(){
        $params=$this->postValidate([
            'id'=>'require',
            'expire_time|过期时间'=>'require'
        ]);
        $re=Card::where('id',$params['id'])->update([
            'expire_time'=>strtotime($params['expire_time'])
        ]);
        return self::success($re);
    }

    /**
     * @authName (激活卡)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (35)
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function activation(){
        $params=$this->postValidate([
            'batch|批次'=>'require',
            'money|金额'=>'require',
            'expire_time|过期时间'=>'require|min:10001',
            'start_number|开始序号'=>'require|max:19999',
            'end_number|结束序号'=>'require',
            'remark|备注'=>'require'
        ]);
        $params['user_id'] = $this->request->admin_id;
        $params['expire_time']=strtotime($params['expire_time']);
        if($params['start_number']>$params['end_number']){
            return self::error('开始序号不能大于结束序号！');
        }
        $batch=CardBatch::where('batch',$params['batch'])->find();
        if(empty($batch)) return self::error('批次错误！');
        CardActivationLog::create($params);//记录激活
        $end=$params['end_number'];
        for($start=$params['start_number'];$start<=$end;$start++){
            Card::where(['batch'=>$params['batch'],'number'=>$start,'is_act'=>2])->update([
                'money'=>$params['money'],
                'expire_time'=>$params['expire_time'],
                'remark'=>$params['remark'],
                'is_act'=>1
            ]);
        }
        return self::success('');
    }


    /**
     * @authName (设置卡失效)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (36)
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setCardStatus(){
        $params=$this->paramsValidate([
            'id'=>'require'
        ]);
        $card = Card::find($params['id']);
        if (!$card) return self::error('用户id非法');
        if($card->status==2) return self::error('激活操作失败，该卡已被使用！');
        $re = Card::where('id', $params['id'])->update(['is_act' => 5]);
        return self::success($re);
    }

    /**批量激活卡
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
//    public function manyAct(){
//        $params=$this->paramsValidate([
//            'start|开始编号'=>'require',
//            'end|结束编号'=>'require'
//        ]);
//        if($params['start']>$params['end']) return self::error('开始编号大于结束编号，请重新输入！');
//        $start_card=Card::where('id',$params['start'])->find();
//        if(empty($start_card)) return self::error('开始编号有误！');
//        $end_card=Card::where('id',$params['end'])->find();
//        if(empty($end_card)) return self::error('结束编号有误！');
//        for($i=$params['start'];$i<=$params['end'];$i++){
//            $card=Card::where('id',$i)->find();
//            $card->is_act=1;
//            $card->save();
//        }
//        return self::success('');
//    }

    /**删除充值卡
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
//    public function delCard(){
//        $params=$this->postValidate([
//            'id'=>'require'
//        ]);
//        return self::success(Card::deleteData($params['id']));
//    }


    /**
     * @authName (充值赠送列表)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (37)
     * @return \think\response\Json
     * @throws \think\db\exception\DbException
     */
    public function rechargeList(){
        $list=Recharge::where('id','>',0)->paginate(10,false);
        return self::success($list);
    }

    /**
     * @authName (添加修改充值赠送)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (38)
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addRecharge(){
        $input=$this->postValidate([
            'id'=>'number',
            'name'=>'require',
            'recharge'=>'require',
            'get_money'=>'require'
        ]);
        return self::success(Recharge::saveData($input));
    }

    /**
     * @authName (删除充值赠送)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (39)
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function delRecharge(){
        $input=$this->postValidate([
            'id'=>'require'
        ]);
        return self::success(Recharge::deleteData($input['id']));
    }

    //导出充值卡表格
    public function cardExcel(){
        $batch = $this->request->get('batch');
        $w=[];
        if(!empty($batch)) {
            $w[]=['batch','in',explode(',',$batch)];
        }
        $card=Card::where($w)->append(['statusText'])
            ->withAttr('status',function ($v){
                $name=[1=>'否',2=>'是'];
                return $name[$v];
            })
            ->withAttr('card_no', function ($v) {
                return "\t" . $v;
            })
            ->visible(['id','card_no','money','password','status','create_time','statusText','expire_time'])
            ->select()
            ->toArray();
        $data=[];
        foreach ($card as $value){
            $data[]=[
                'id'=>$value['id'],
                'card_no'=>$value['card_no'],
                'password'=>$value['password'],
                'status'=>$value['status'],
//                'money'=>$value['money'],
                'create_time'=>$value['create_time'],
                'statusText'=>$value['statusText'],
            ];
        }
        $arr=[];
        foreach ($data as $k => $v) {
            $arr[] = array_values($v);
        }
        $excel = [
            'save_name' => '充值卡列表',
            'table' => [
                // 表格1
                'sheet1' => [
                    // 工作表标题
                    'title' => '充值卡列表',
                    // 表格标题
                    'table_captain' => '充值卡',
                    // 边框
                    'border' => true,
                    // 字段
                    'field' => [
                        [
                            '卡编号',
                            ['width' => 15]
                        ],
                        [
                            '充值卡号',
                            ['width' => 20]
                        ],
                        [
                            '密码',
                            ['width' => 20]
                        ],
                        [
                            '是否被使用',
                            ['width' => 20]
                        ],
//                        [
//                            '充值卡金额（单位：元）',
//                            ['width' => 30]
//                        ],
                        [
                            '创建时间',
                            ['width' => 30]
                        ],
                        [
                            '激活状态',
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
}
