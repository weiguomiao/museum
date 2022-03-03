<?php

namespace app\common\service;

use app\common\exception\AppRuntimeException;
use mytools\annotation\authmanage\AuthManager;
use mytools\lib\HexBinStr;
use think\facade\Console;

/**
 * 权限管理层
 * Class AuthorityService
 * @package app\common\service
 */
class AuthorityService
{
    /**
     * 校验是否有访问权限
     * @param string $auth_str 原始的权限字符串
     * @throws AppRuntimeException
     * @throws \ReflectionException
     * @throws \think\db\exception\DbException
     */
    public static function checkAuth($auth_str)
    {
        // 获取当前路由的调度信息
        $controller = 'app\admin\controller\\'
            . str_replace('.','\\',request()->controller())
            . config('route.controller_suffix');

        $action = request()->action();
        // 获取类和方法的备注
        $auth_arr = (new AuthManager())->setDir($controller . '@' . $action)->run()['auth_info'][0]['item'][$action] ?? [];

        if(!empty($auth_arr) && $auth_arr['isCheck']) {
            // 未通过
            if(!(new HexBinStr())->decodeHex(HexBinStr::deduceStr($auth_str))->isTrue($auth_arr['authIndex'])){
                throw new AppRuntimeException('权限非法');
            }
        }
    }

    // 刷新权限
    public static function refresh()
    {
        Console::call('auth', ['admin/controller']);
    }


    /**
     * 获取权限列表
     * @return mixed
     * @throws AppRuntimeException
     * @throws \ReflectionException
     * @throws \think\db\exception\DbException
     */
    public static function getAuthList()
    {
        $list = cache(AuthManager::INFO_CACHE);
        if(empty($list)) {
            self::refresh();
            $list = cache(AuthManager::INFO_CACHE);
        }
        return $list;
    }

    /**
     * 获取权限索引数据
     * @return mixed
     */
    public static function getAuthIndex()
    {
        $list = cache(AuthManager::INDEX_CACHE);
        if(empty($list)) {
            self::refresh();
            $list = cache(AuthManager::INDEX_CACHE);
        }
        return $list;
    }


    /**
     * 根据原始权限字符串获取权限菜单ID
     * @param string $auth_str 原始权限字符串
     * @return array
     * @throws AppRuntimeException
     * @throws \ReflectionException
     * @throws \think\db\exception\DbException
     */
    public static function getMenuList(string $auth_str)
    {
        $index = ((new HexBinStr())->decodeHex(HexBinStr::deduceStr($auth_str)))->getIndex();
        $auth_info = self::getAuthList();
        $auth_index = self::getAuthIndex();
        // 从权限index获取菜单信息
        $menu = [];
        foreach ($index as $i) {
            if(isset($auth_arr['auth_index'][$i])) {
                $menu[] = $auth_info[$auth_index[$i][0]]['menuId'];
                $menu[] = $auth_info[$auth_index[$i][0]]['item'][$auth_index[$i][1]]['menuID'];
            }
        }
        return array_unique($menu);
    }

    /**
     * 根据权限ID生成权限字符串
     * @param array $index 权限index数组
     * @return string
     * @throws \Exception
     */
    public static function createStr(array $index)
    {
        return HexBinStr::reduceStr((new HexBinStr())->createBinStr(50)->setMBit($index)->getStr());
    }
}
