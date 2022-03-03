<?php

namespace app\admin\controller;

use app\BaseController;
use app\common\enum\HttpCode;
use app\common\exception\AppRuntimeException;
use app\common\service\AuthorityService;
use mytools\lib\Token;

/**
 * admin基础控制器
 * Class BaseController
 * @package app\admin\controller
 */
class AdminBaseController extends BaseController
{
    /**
     * @var int 默认分页时每页记录数
     */
    protected $default_limit = 10;

    /**
     * 初始化控制器时操作
     * @throws \ReflectionException
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DbException
     */
    public function initialize()
    {
        // 校验token
        if (empty($this->request->param('token'))) {
            throw new AppRuntimeException('请先登录！',null,HttpCode::RETURN_LOGIN);
        }
        //校验token
        $token = Token::read($this->request->param('token'));
        if($token['typ'] != Token::TYPE_ADMIN) {
            throw new AppRuntimeException('身份错误！',null,HttpCode::RETURN_LOGIN);
        }
        $this->request->admin_id = $token['uid'];
        // 校验权限
        if($token['ext']['auth'] != '.f,50.')
            AuthorityService::checkAuth($token['ext']['auth']);
    }
}
