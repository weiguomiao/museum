<?php
declare (strict_types=1);

namespace app\admin\controller;

use app\common\model\Activity;
use app\common\model\ActivityLog;
use app\common\model\BookLog;
use app\common\model\BookPt;
use mytools\lib\QrCodeService;

/**
 * 活动管理
 * Class ActivityController
 * @package app\admin\controller]
 */
class ActivityController extends AdminBaseController
{
    /**
     * @authName (活动列表)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (50)
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DbException
     */
    public function activityList()
    {
        $input = $this->postValidate([
            'keyword' => '',
            'status' => 'number'
        ]);
        $w = Activity::makeWhere($input, [
            ['id|title', 'like', 'keyword'],
            ['status', '=']
        ]);
        $list = Activity::where($w)->paginate(10, false);
        return self::success($list);
    }

    /**
     * @authName (添加修改活动)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (51)
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addActivity()
    {
        $input = $this->postValidate([
            'id' => 'number',
            'title|活动标题' => 'require',
            'images|活动图片' => 'require',
            'desc|活动详情' => 'require',
            'status|状态' => 'require',
            'all_num|可报名人数' => 'require',
            'sign_start_time|签到开始时间' => 'require',
            'sign_end_time|签到结束时间' => 'require',
        ]);
        $input['sign_start_time'] = strtotime($input['sign_start_time']);
        $input['sign_end_time'] = strtotime($input['sign_end_time']);
        if ($input['sign_end_time'] < $input['sign_start_time']) return self::error('签到开始时间不能小于签到结束时间哦！');
        return self::success(Activity::saveData($input));
    }

    /**
     * @authName (修改活动状态)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (52)
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setActStatus()
    {
        $input = $this->postValidate([
            'id' => 'require'
        ]);
        return self::success(Activity::saveStatus($input['id']));
    }

    /**
     * @authName (预约平台列表)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (53)
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DbException
     */
    public function bookList()
    {
        $input = $this->paramsValidate([
            'mode' => 'in:1,2',//1博物馆，2图书馆
            'status' => 'in:1,2',
            'time' => '',
            'page' => ''
        ]);
        $w = [];
        if (!empty($input['status'])) $w[] = ['status', '=', $input['status']];
        if (empty($input['mode'])) $w[] = ['ven_id', '=', 1]; else $w[] = ['ven_id', '=', $input['mode']];
        $beginToday = strtotime(date('Y-m-d'));
        if (!empty($input['time'])) {
            $time = $input['time'];
            $startTime = strtotime($time[0]);
            $endTime = strtotime($time[1]);
            $w[] = ['time', 'between', [$startTime - 1, $endTime + 1]];
        } else {
            $w[] = ['time', '>=', $beginToday];
        }
        $list = BookPt::where($w)
            ->append(['week_val'])
            ->withAttr('time', function ($v) {
                return date('Y-m-d', $v);
            })
            ->order('time', 'asc')
            ->paginate(7, 14);
        return self::success($list);
    }

    /**
     * @authName (预约平台详情)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (54)
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function bookInfo()
    {
        $input = $this->postValidate([
            'id' => 'require'
        ]);
        $book = BookPt::where('id', $input['id'])
            ->withAttr('time', function ($v) {
                return date('Y-m-d', $v);
            })
            ->visible(['id', 'number', 'status', 'time'])
            ->find();
        return self::success($book);
    }

    /**
     * @authName (修改预约平台)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (55)
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DbException
     */
    public function setBook()
    {
        $input = $this->postValidate([
            'id' => 'require',
            'number' => 'require',
            'status' => 'require|in:1,2'
        ]);
        BookPt::where('id', $input['id'])->update([
            'number' => $input['number'],
            'status' => $input['status']
        ]);
        return self::success('');
    }

    public function setBookStatus()
    {
        $input = $this->postValidate([
            'id' => 'require'
        ]);
        return self::success(BookPt::saveStatus($input['id']));
    }


    /**
     * @authName (查看用户报名活动记录)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (56)
     * @return \think\response\Json
     * @throws \think\db\exception\DbException
     */
    public function actLog()
    {
        $id = $this->request->post('id');
        if (empty($id)) return self::error('参数错误！');
        $data = ActivityLog::where('act_id', $id)
            ->append(['activity', 'status_val'])
            ->order('create_time', 'desc')
            ->paginate($this->default_limit, false)
            ->toArray();
        return self::success($data);
    }

    /**
     * @authName (查看用户预约记录)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (57)
     * @return \think\response\Json
     * @throws \think\db\exception\DbException
     */
    public function bookLog()
    {
        $id = $this->request->post('id');
        if (empty($id)) return self::error('参数错误！');
        $data = BookLog::where('book_id', $id)
            ->append(['status_val', 'real_name'])
            ->order('create_time', 'desc')
            ->paginate($this->default_limit, false)
            ->toArray();
        return self::success($data);
    }

}
