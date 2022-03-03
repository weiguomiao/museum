<?php

namespace app\admin\controller;

use app\common\model\Banner;
use app\common\model\Config;
use app\common\service\ConfigService;

/**
 * 系统配置
 * @menuId 2
 * Class ConfigController
 * @package app\admin\controller
 */
class ConfigController extends AdminBaseController
{
    /**
     * @authName (查看配置表)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (12)
     * @return \think\response\Json|\think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $data = Config::order('sort','asc')->select();
        return self::success($data);
    }


    /**
     * @authName (修改配置数据)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (13)
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function update()
    {
        $input = $this->request->only(['key', 'value', 'id']);
        if(empty($input)) return self::error('参数错误');
        if(!empty($input['id'])) $input['key'] = $input['id'];
        return self::success(ConfigService::ConfigSave($input['key'],$input['value']));
    }


    public function banner()
    {
        $data = Banner::select();
        return self::success($data);
    }

    public function bannerSave()
    {
        $input = $this->postValidate([
            'id' => 'number',
            'image|图片' => 'require',
            'title|图片名称' => 'require',
            'data|跳转链接' => '',
            'status|状态' => 'require',
            'type|跳转类型' => '',
            'ap_id|广告位置' => '',
            'remark|备注' => '',
        ]);

        return self::success(Banner::saveData($input));
    }

    /**
     * 修改图片状态
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function bannerStu()
    {
        $id = $this->request->post('id', 0, 'intval');
        return $this->success(Banner::saveStatus($id));
    }

    /**
     * 排序置顶
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function bannerTop()
    {
        $input = $this->postValidate([
            'id|ID' => 'require',
        ]);
        $input['sort'] = time();
        return $this->success(Banner::saveData($input));
    }

    public function delBanner()
    {
        $input = $this->postValidate([
            'id|ID' => 'require',
        ]);
        return self::success(Banner::deleteData($input));
    }
}
