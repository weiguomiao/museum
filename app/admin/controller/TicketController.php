<?php
declare (strict_types=1);

namespace app\admin\controller;

use app\common\model\Calendar;
use app\common\model\Guide;
use app\common\model\Ticket;
use app\common\model\TicketOrder;

/**
 * 门票管理
 * Class TicketController
 * @package app\admin\controller
 */
class TicketController extends AdminBaseController
{
    /**
     * @authName (门票列表)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (58)
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DbException
     */
    public function ticketList()
    {
        $input = $this->postValidate([
            'key' => '',
            'status' => 'in:1,2'
        ]);
        $w = Ticket::makeWhere($input, [
            ['title', 'like', 'key'],
            ['status', '=']
        ]);
        $list = Ticket::where($w)->order('create_time desc')->paginate(10, false);
        return self::success($list);
    }

    /**
     * @authName (设置门票状态)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (59)
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setTicketStatus()
    {
        $input = $this->postValidate([
            'id|ID' => 'require'
        ]);
        return self::success(Ticket::saveStatus($input['id']));
    }

    /**
     * @authName (门票编辑)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (60)
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function ticketSave()
    {
        $input = $this->postValidate([
            'id' => 'number',
            'title|门票名称' => 'require',
            'content|购买须知' => 'require',
            'price|购买价格' => 'require',
            'status|状态' => 'require|in:1,2'
        ]);
        return self::success(Ticket::saveData($input));
    }

    /**
     * @authName (门票订单列表)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (61)
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DbException
     */
    public function ticketOrderList()
    {
        $input = $this->postValidate([
            'key' => '',
            'status' => 'in:1,2,3,4'
        ]);
        $w = TicketOrder::makeWhere($input, [
            ['id', 'like', 'key'],
            ['status', '=']
        ]);
        $list = TicketOrder::where($w)->append(['ticket_name', 'status_val', 'refund_order'])->order('create_time desc')->paginate(10, false);
        return self::success($list);
    }

    /**
     * @authName (购票须知和收费标准)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (62)
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function guide()
    {
        $list = Guide::select();
        return self::success($list);
    }

    /**
     * @authName (编辑购票须知和收费标准)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (63)
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DbException
     */
    public function guideSave()
    {
        $input = $this->postValidate([
            'id|ID' => 'require',
            'title|标题' => 'require',
            'content|内容' => 'require'
        ]);
        return self::success(Guide::update($input));
    }

    /**
     * @authName (日历列表)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (64)
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DbException
     */
    public function calenderList()
    {
        $input = $this->postValidate([
            'time' => 'array'
        ]);
        $w = [];
        if (!empty($input['time'])) {
            $time = $input['time'];
            $w[] = ['time', 'between', [strtotime($time[0]), strtotime($time[1])]];
        }
        $list = Calendar::where($w)
            ->withAttr('time', function ($v) {
                return date('Y-m-d', $v);
            })->append(['status_val'])
            ->paginate(10, false);
        return self::success($list);
    }

    /**
     * @authName (编辑日历)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (65)
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function calenderSave()
    {
        $input = $this->postValidate([
            'id' => 'number',
            'time|日期' => 'require|dateFormat:Y-m-d',
            'status' => 'require|in:1,2'
        ]);
        $input['time']=strtotime($input['time']);
        return self::success(Calendar::saveData($input));
    }

    /**
     * @authName (刪除日历)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (66)
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function delCalender()
    {
        $input = $this->postValidate([
            'id' => 'require'
        ]);
        return self::success(Calendar::deleteData($input['id']));
    }
}
