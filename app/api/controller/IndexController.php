<?php
declare (strict_types = 1);

namespace app\api\controller;

use app\BaseController;
use app\common\enum\HttpCode;
use app\common\model\User;
use app\Request;
use EasyWeChat\Factory;
use mytools\lib\Token;
use mytools\resourcesave\ResourceManager;
use think\facade\Log;

class IndexController extends BaseController
{
    protected $app;

    public function __construct()
    {
        $this->app = Factory::officialAccount(config('wechat.official_account'));
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \EasyWeChat\Kernel\Exceptions\BadRequestException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \ReflectionException
     */
    public function msgServer()
    {
        try {
            $this->app->server->push(function ($message) {
//                Log::record($message, 'error');
                switch ($message['MsgType']) {
                    case 'event':
                        // openid
                        $openid = $message['FromUserName'];
                        $type = $message['Event'];

                        switch ($type) {
                            case "subscribe":// 订阅
                                // 获取unionid
                                $user = $this->app->user->get($openid);
                                $filename = 'head_img/' .$openid . '.jpg';
                                ResourceManager::DownPicSave($user['headimgurl'], 'uploads/' . $filename);
                                // 判断在表里是否存在，存在写入用户信息，不存在则注册用户
                                $u = User::where('openid', $openid)->find();
                                if (empty($u)) {
                                    User::create([
                                        'openid' => $openid,
                                        'nickname'=>filterNickname($user['nickname']),
                                        'head_image'=>$filename
                                    ]);
                                }else{
                                    User::where('openid', $openid)->update(['status' => 1]);
                                }
                                break;
                            case "unsubscribe":// 删除openid
                                User::where('openid', $openid)->update(['status' => 2]);
                                break;
                        }

                        return '您好，欢迎关注华南博物馆微信公众号';
                        break;
                    case 'text':

                        return '我没听清你说啥，大声点^_^';
                        break;
                    case 'image':
                        return '收到图片消息';
                        break;
                    case 'voice':
                        return '收到语音消息';
                        break;
                    case 'video':
                        return '收到视频消息';
                        break;
                    case 'location':
                        return '收到坐标消息';
                        break;
                    case 'link':
                        return '收到链接消息';
                        break;
                    case 'file':
                        return '收到文件消息';
                    // ... 其它消息
                    default:
                        return '收到其它消息';
                        break;
                }
            });
        } catch (\Exception $e) {
        }

        // 在 laravel 中：
        $response = $this->app->server->serve();
        // 对于需要直接输出响应的框架，或者原生 PHP 环境下
        $response->send();
        // 而 laravel 中直接返回即可：
        return $response;
    }


    /**登录
     * @return \think\response\Json
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function login(){
        $code=request()->post('code');
        if (empty($code)) return self::error('缺少code参数');
        try {
            $auth=$this->app->oauth->getAccessToken($code);
        }catch (\Exception $e){
            return self::error($e->getMessage());
        }
        if (empty($auth['openid'])) return self::error('发生错误，请重试！');
        $user=User::where('openid',$auth['openid'])->find();
        $wxUser = $this->app->oauth->user($auth);
        $filename = 'head_img/' .$auth['openid'] . '.jpg';
        ResourceManager::DownPicSave($wxUser['avatar'], 'uploads/' . $filename);
        if(empty($user)){
            $user=User::create([
                'openid' => $auth['openid'],
                'nickname'=>filterNickname($wxUser['nickname']),
                'head_image'=>$filename
            ]);
        }
        return self::success( ['openid' =>$user['openid']],
            HttpCode::SUCCESS,
            200,
            ['Access-Control-Expose-Headers' => 'token',
                'token' => Token::make((int)$user['id'], Token::TYPE_USER)
            ]
        );
    }

    /**
     * 获取微信JSSDK
     * @return \think\response\Json
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function wxJsSdk()
    {
        $request = request()->param();
        if (!$request['url']) {
            return self::error('缺少请求参数');
        }
        try {
            $app = Factory::officialAccount(config('wechat.official_account'));
            $app->jssdk->setUrl($request['url']);
            $wxSdk = $app->jssdk->buildConfig($request['jsApiList'], $debug = true, $beta = false, $json = false);
        } catch (\Exception $e) {
            return self::error($e->getMessage());
        }
        return self::success($wxSdk);
    }

    /**
     * 微信网页授权
     */
    public function wxRedirect(Request $request)
    {
        $params = request()->param();
        if (strpos(request()->header('USER_AGENT'), 'MicroMessenger') === false) {
            return self::success('请在微信中浏览此页面');
        }
        if (isset($params['code'])) {
            $module = request()->param('module');
            if (!$module) {
                $this->error("参数错误！");
            }
            $url = urldecode($module);
            $urlArr = explode('?', $url);
            if (count($urlArr) > 1) {
                $param = $this->convertUrlQuery($urlArr[1]);
            }
            $param['code'] = $params['code'];
            $to = $urlArr[0] . '?' . http_build_query($param);
            Header("Location:" . $to);
            exit;
        }
        $app = Factory::officialAccount(config('wechat.official_account'));
        $response = $app->oauth->scopes(['snsapi_userinfo'])->redirect(request()->url(true));
        $response->send();
    }

    /**
     * 解析url中参数信息，返回参数数组
     */
    private function convertUrlQuery($query)
    {
        $queryParts = explode('&', $query);

        $params = array();
        foreach ($queryParts as $param) {
            $item = explode('=', $param);
            $params[$item[0]] = $item[1];
        }

        return $params;
    }
}
