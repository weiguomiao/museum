<?php
/**
 * Created by PhpStorm.
 * User: bzg
 * Date: 2019/10/25
 * Time: 14:05
 */

namespace app;


use app\common\exception\AppRuntimeException;
use think\helper\Str;
use think\Model;

/**
 * 基础模型类
 * Class BaseModel
 * @package app
 */
abstract class BaseModel extends Model
{

    /**
     * 构造条件列表
     * @param array $request 请求参数数组
     * @param array $field 条件字段数组
     *  [
     *      [
     *          数据库字段名,
     *          对比条件,
     *          前端传来的键名 | 当对比条件为bt时： [开始键名，结束键名] | 开始键名
     *      ]
     *  ]
     * @param string $asn 表别名
     * @return array 条件数组
     */
    public static function makeWhere(array $request,array $field, string $asn = '')
    {
        $w = [];
        if(!empty($field) && !empty($request)) {
            foreach ($field as $v) {
				$as = $asn;
                // 兼容【表别名.字段名写法】
                $field_arr = explode('.',$v[0]);
                if(count($field_arr) == 2) {
                    $v[0] = $field_arr[1]; // 字段名
                    $as = $field_arr[0]; // 别名
                }
                // 判断前端给的键名
                $key = empty($v[2]) ? $v[0] : $v[2];
                // 判断条件，如果条件为空，默认为=
                $v[1] = empty($v[1]) ? '=' : $v[1];
                if($v[1] == 'bt') {
                    $key = is_array($key) ? $key[0] : $key;
                }

                if (!empty($request[$key]) || in_array($v[1],['like','bt'])) {
                    // 将字段名组装为 表明.字段名
                    if ($as) $f = $as . '.' . $v[0];
                    else $f = $v[0];
                    switch ($v[1]) {
                        case 'like': // like字段
                            $key = empty($v[2]) ? 'key' : $v[2];
                            if (!empty($request[$key])) {
                                $w[] = [$f, $v[1], '%' . $request[$key] . '%'];
                            }
                            break;

                        case 'bt': // 在**之间，默认前端字段为start,end
                            // 给默认值
                            if(empty($v[2])) {
                                $v[2] = ['start','end'];
                            }
                            // 解析规则
                            if(is_array($v[2])) {
                                if(!empty($request[$v[2][0]]) && !empty($request[$v[2][1]])){
                                    $w[] = [$f,'>=',$request[$v[2][0]]];
                                    $w[] = [$f,'<',$request[$v[2][1]]];
                                }
                            }elseif(is_string($v[2])){
                                if(!empty($request[$v[2]])){
                                    $w[] = [$f,'>=',$request[$v[2]]];
                                }
                            }
                            break;

                        default: // in,>,>=,<,<=字段
                            $w[] = [$f, $v[1], $request[$key]];
                            break;
                    }
                }
            }
        }
        return $w;
    }

    /**
     * 新增修改
     * @param array $param 数据参数
     * @param array $u 验证条件 ['需验证的字段'=>'值',]
     * @return BaseModel|Model
     * @throws AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function saveData(array $param, $u = [])
    {
        $pk = (new static())->getPk();
        // 保存入库
        if(empty($param[$pk])){
            $re = self::create($param);
        } else {
            if(!empty($u)) {
                self::check($param[$pk],$u);
            }
            $re = self::update($param);
        }

        if($re) return $re;
        throw new AppRuntimeException('操作失败');
    }


    /**
     * 修改状态
     * @param int $id 记录ID
     * @param string|array $field 修改的字段 (为字符串则是修改的字段名，默认1和2切换，为数组则指定字段指定值['字段'=>值,])
     * @param array $u 验证条件 ['需验证的字段'=>'值',]
     * @return bool
     * @throws AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function saveStatus($id, $field = 'status', $u = [])
    {
        if((int)$id <= 0) throw new AppRuntimeException('请传入记录ID');
        if(!$row = self::find((int)$id)) throw new AppRuntimeException('未查询到该条记录');
        if(!empty($u)) {
            self::check($row,$u);
        }
        if(is_array($field)) { // 自定义状态值
            foreach ($field as $k=>$v) {
                $row[$k] = $v;
            }
        }else{
            $row[$field] = $row[$field] == 1 ? 2 : 1;
        }
        if($row->save()) return true;
        throw new AppRuntimeException('修改失败');
    }


    /**
     * 删除数据
     * @param int|array $id  记录ID或者数组
     * @param array $u 验证条件 ['需验证的字段'=>'值',]
     * @return bool
     * @throws AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function deleteData($id, $u = [])
    {
        if(is_array($id) && empty($id)) {
            throw new AppRuntimeException('请传入记录ID');
        }else{
            if((int)$id <= 0) {
                throw new AppRuntimeException('请传入记录ID');
            }
        }

        if(!empty($u)) {
            if (is_array($id)) {
                foreach ($id as $d) {
                    self::check($d, $u);
                }
            }else{
                self::check($id,$u);
            }
        }
        self::destroy($id);
        return true;
    }


    /**
     * 验证修改是否非法
     * @param $pk
     * @param $u
     * @throws AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private static function check($pk, $u)
    {
        if($pk instanceof self) {
            $old = $pk;
        }else{
            $old = self::find((int)$pk);
        }

        if(!$old) throw new AppRuntimeException('操作的数据不存在');
        foreach ($u as $k => $v) {
            if(is_array($v)) {
                if(!in_array($old[$k],$v)) {
                    throw new AppRuntimeException('操作非法');
                }
            }else{
                if($v != $old[$k]) {
                    throw new AppRuntimeException('操作非法');
                }
            }
        }
    }


    /**
     * 获取常量属性
     * @param $name
     * @param $argv
     * @return mixed|null
     */
    public static function __callStatic($name, $argv)
    {
        // 驼峰转下划线
        $fun_name = explode('_',Str::snake($name));
        $prefix = array_shift($fun_name);
        $suffix = array_pop($fun_name);
        // 检查是否是get属性的操作
        if($prefix == 'get' && in_array($suffix,['index','desc','val'])) {
            $const = strtoupper(implode('_',$fun_name));
            try{
                if($const_data = constant( static::class .'::'. $const)) {
                    array_unshift($argv,$const_data);
                    return call_user_func_array(self::class . '::get'.ucfirst($suffix),$argv);
                }
            }catch (\Exception $e) {
                return null;
            }
        }else{
            return parent::__callStatic($name, $argv);
        }
    }

    /**
     * 获取常量的数值
     * @param array $const
     * @param string $index
     * @return mixed
     */
    public static function getIndex(array $const, string $index)
    {
        return $const[$index][0] ?? null;
    }

    /**
     * 获取常量描述
     * @param array $const
     * @param string $index
     * @return mixed
     */
    public static function getDesc(array $const, string $index)
    {
        return $const[$index][1] ?? null;
    }

    /**
     * 根据数值获取描述
     * @param array $const
     * @param int $key
     * @return mixed|null
     */
    public static function getVal(array $const, int $key)
    {
        foreach ($const as $v) {
            if($v[0] == $key) {
                return $v[1];
            }
        }
        return null;
    }

}