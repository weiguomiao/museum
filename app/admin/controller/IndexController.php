<?php

namespace app\admin\controller;

use app\BaseController;
use app\common\model\Admin;
use app\common\model\AdminRole;
use app\common\service\AuthorityService;
use mytools\lib\Openssl;
use mytools\lib\QrCodeService;
use mytools\lib\Token;
use mytools\lib\ToolBag;
use mytools\resourcesave\ResourceManager;


/**
 * 后台其他
 * Class IndexController
 * @package app\admin\controller
 */
class IndexController extends BaseController
{
    /**
     * 管理员登录
     * @return \think\response\Json
     * @throws \ReflectionException
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loginHandle()
    {
        $input = $this->postValidate([
            'name|登录名' => 'require',
            'pwd|密码' => 'require',
        ]);
        $w = [
            'login_name' => $input['name'],
            'pwd' => ToolBag::password($input['pwd'])
        ];

        $admin = Admin::where($w)->find();
        if (!$admin) return self::error('用户名或密码错误');
        if ($admin->getData('status') != 1) return self::error('该用户已被禁用，请联系管理员');

        $admin = $admin->toArray();
        // 根据角色ID来生成权限字符串
        if (in_array(1001, $admin['role_id'])) {
            $auth_str = '.f,50.';
            $auth_list = array_keys(AuthorityService::getAuthIndex());
        } else {
            $auth_lists = AdminRole::where('id', 'in', $admin['role_id'])->column('auth_list');
            $auth_list = [];
            foreach ($auth_lists as $al) {
                $auth_list = array_merge($auth_list, json_decode($al, true));
            }
            $auth_list = array_unique($auth_list);
            $auth_str = AuthorityService::createStr($auth_list);
        }

        // 生成token
        $token = Token::make($admin['id'], Token::TYPE_ADMIN, ['auth' => $auth_str]);
        // 渲染主页面
        return self::returnJson(['auth_list' => $auth_list, 'token' => $token, 'login_name' => $admin['login_name']],
            '登录成功',
            1,
            200,
            ['Access-Control-Expose-Headers' => 'Token', 'token' => $token]
        );
    }

    /**
     * 文件异步上传
     * @return \think\response\Json
     */
    public function uploadFile()
    {

        return self::success(ResourceManager::staticResource(ResourceManager::saveBase64('upload_file', 'file')));
    }

    // 获取各种状态
    public function getStatus()
    {
        $input = $this->postValidate([
            'class' => 'require',
            'const' => 'require'
        ]);
        $class = 'app\common\model\\' . $input['class'];
        if (!class_exists($class)) {
            $class = $input['class'];
            if (!class_exists($class)) {
                return self::error('类不存在');
            }
        }
        try {
            if ($const_data = constant($class . '::' . $input['const'])) {
                return self::success(getModelConstArray($const_data));
            }
        } catch (\Exception $e) {
            return self::error('常量不存在');
        }
    }

    /**活动报名
     * @return \think\response\Json
     */
    public function getQrCode()
    {
        $id = $this->request->post('id');
        $config = [
            'generate' => 'writefile',
            'size' => 400,
            'file_name' => 'uploads/qrCode'
        ];
        $content = "http://huananmuseum.sdream.top/h5/#/registration?id=" . $id;
        $re = (new QrCodeService($config))->createServer($content);
        if ($re['success'] != 'true') {
            return self::error($re['message']);
        }
        return self::success(config('conf.static_url') . '/' . $re['data']['url']);
    }

    /**活动签到
     * @return \think\response\Json]
     */
    public function getSignQrCode()
    {
        $id = $this->request->post('id');
        $config = [
            'generate' => 'writefile',
            'size' => 400,
            'file_name' => 'uploads/qrCode'
        ];
        $content = "http://huananmuseum.sdream.top/h5/#/myEnlist?id=" . $id.'&mode=1';
        $re = (new QrCodeService($config))->createServer($content);
        if ($re['success'] != 'true') {
            return self::error($re['message']);
        }
        return self::success(config('conf.static_url') . '/' . $re['data']['url']);
    }

    /**扫码支付
     * @return \think\response\Json
     */
    public function getPayQrCode()
    {
        $id = $this->request->post('id');
        $config = [
            'generate' => 'writefile',
            'size' => 400,
            'file_name' => 'uploads/qrCode'
        ];
        $content = "http://huananmuseum.sdream.top/h5/#/pay_meth?id=" . Openssl::encrypt($id);
        $re = (new QrCodeService($config))->createServer($content);
        if ($re['success'] != 'true') {
            return self::error($re['message']);
        }
        return self::success(config('conf.static_url') . '/' . $re['data']['url']);
    }
}
