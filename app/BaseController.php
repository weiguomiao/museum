<?php
declare (strict_types = 1);

namespace app;

use app\common\enum\HttpCode;
use app\common\exception\AppRuntimeException;
use app\common\validate\BaseValidate;
use think\App;
use think\exception\ValidateException;

/**
 * 控制器基础类
 */
abstract class BaseController
{
    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * 是否批量验证
     * @var bool
     */
    protected $batchValidate = false;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [];

    /**
     * 每页记录数
     * @var int
     */
    protected $default_limit = 10;

    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     * @throws \Exception
     */
    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = $this->app->request;

        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {}

    /**
     * 验证数据
     * @access protected
     * @param  array        $data     数据
     * @param  string|array $validate 验证器名或者验证规则数组
     * @param  array        $message  提示信息
     * @param  bool         $batch    是否批量验证
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate(array $data, $validate, array $message = [], bool $batch = false)
    {
        if (is_array($validate)) {
            $v = new BaseValidate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                list($validate, $scene) = explode('.', $validate);
            }
            $class = false !== strpos($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);
            $v     = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        $v->message($message);

        // 是否批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        $v->failException(true)->check($data);
        return $data;
    }

    /**
     * @param $validate
     * @param array $message
     * @param bool $batch  是否批量验证
     * @param string $method
     * @return array|string|true
     * @throws AppRuntimeException
     * @throws \think\db\exception\DbException
     */
    protected function paramsValidate($validate, array $message = [], bool $batch = false, string $method = 'param')
    {
        try {
            // 只取验证器中有的数据
            $keys = array_keys($validate);
            $keys = array_map(function ($v) {
                if(is_string($v))
                    return explode('|',$v)[0];
            },$keys);
            $data = $this->request->$method($keys);
            // 去除验证器中空的验证值
            $validate = array_diff($validate,['']);
            return $this->validate($data, $validate, $message, $batch);
        }catch (ValidateException $e) {
            throw new AppRuntimeException($e->getMessage());
        }
    }

    /**
     * 验证get请求参数
     * @param array|string      $validate       验证数组或者验证器对象
     * @param array             $message        错误消息
     * @param bool              $batch          是否批量验证
     * @return array|string|true
     * @throws AppRuntimeException
     * @throws \think\db\exception\DbException
     */
    protected function getValidate($validate, array $message = [], bool $batch = false)
    {
        return $this->paramsValidate($validate, $message, $batch, 'get');
    }

    /**
     * 验证post请求参数
     * @param array|string      $validate       验证数组或者验证器对象
     * @param array             $message        错误消息
     * @param bool              $batch          是否批量验证
     * @return array|string|true
     * @throws AppRuntimeException
     * @throws \think\db\exception\DbException
     */
    protected function postValidate($validate, array $message = [], bool $batch = false)
    {
        return $this->paramsValidate($validate, $message, $batch, 'post');
    }

    /**
     * 返回json数据
     * @param mixed $data 响应数据
     * @param string $msg 错误消息
     * @param int $code 响应码
     * @param int $status_code 响应状态码
     * @param array $header 响应头
     * @return \think\response\Json
     */
    protected static function returnJson($data, $msg, int $code, int $status_code, array $header = [])
    {
        return json(['data' => $data, 'msg' => $msg, 'code' => $code])->code($status_code)->header($header);
    }

    /**
     * 返回成功json
     * @param $data
     * @param int $code
     * @param int $status_code
	 * @param array $header 响应头
     * @return \think\response\Json
     */
    public static function success($data, int $code = HttpCode::SUCCESS, int $status_code = 200, array $header = [])
    {
        return self::returnJson($data,'', $code, $status_code, $header);
    }

    /**
     * 返回错误json
     * @param string $msg 错误消息
     * @param int $code
     * @param int $status_code
	 * @param array $header 响应头
     * @return \think\response\Json
     */
    public static function error(string $msg, int $code = HttpCode::ERROR, int $status_code = 200, array $header = [])
    {
        return self::returnJson(null, $msg, $code, $status_code, $header);
    }

    /**
     * 重定向跳转
     * @param string $path
     * @param array $vars
     * @return \think\response\Redirect
     */
    public function redirect(string $path,$vars = [])
    {
        $url = (string) url($path,$vars);
        return header('location:' . $url);
    }

}
