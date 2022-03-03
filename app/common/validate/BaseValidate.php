<?php


namespace app\common\validate;

use mytools\lib\GlobalParam;
use mytools\lib\ValidateTool;
use think\Validate;

/**
 * 闭包验证合集
 * Class ValidateClosure
 * @package app\common\validate
 */
class BaseValidate extends Validate
{
    /**
     * 调用验证工具类
     * @param $value
     * @param $method
     * @param array $data
     * @param string $key
     * @return bool
     */
    public function use($value ,string  $method, array $data, string $key)  : bool
    {
        return call_user_func_array([ValidateTool::class,$method],[$value]) ? true : false;
    }

    /**
     * 判断记录是否存在且保存模型对象至全局变量
     * @param $value
     * @param string $model
     * @param array $data
     * @param string $key
     * @return bool
     */
    public function model($value ,string  $model, array $data, string $key) : bool
    {
        $pk = is_numeric($value) ? (int)$value : $value;
        $model_class = class_exists($model) ? $model : ('app\common\model\\' . $model);
        if(!class_exists($model_class)) return false;
        if(!$m = $model_class::find($pk)) return false;
        GlobalParam::set($key . '_model',$m);
        return true;
    }
}