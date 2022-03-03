<?php
declare (strict_types = 1);

namespace app\api\middleware;

use app\BaseController;
use app\common\enum\HttpCode;
use app\common\model\User;
use mytools\lib\Token;

/**
 * 校验token
 * Class ApiTokenMiddleware
 * @package app\middleware
 */
class ApiTokenMiddleware
{
    /**
     * @param $request
     * @param \Closure $next
     * @return mixed|\think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function handle($request, \Closure $next)
    {
        //建表后取消注释
        $param = $request->param();
        if (empty($param['token'])) {
            return BaseController::error('请先登录！',HttpCode::RETURN_LOGIN);
        }
        //校验token
        $token = Token::read($param['token']);

        if($token['typ'] != Token::TYPE_USER) {
            return BaseController::error('身份错误！',HttpCode::RETURN_LOGIN);
        }
        // 自动续期
        if ($token['gqt'] - time() < 864000) {
            response()->header([
                'Access-Control-Expose-Headers' => 'token',
                'token' => Token::make((int)$token['uid'], Token::TYPE_USER)
            ]);
        }
        $request->user_id = $token['uid'];
        $user=User::where('id',$token['uid'])->find();
        if($user->status!=1){
            return BaseController::error('身份异常！',HttpCode::RETURN_LOGIN);
        }
        $request->user=$user;
        return $next($request);
    }
}
