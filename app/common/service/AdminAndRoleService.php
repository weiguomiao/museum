<?php

namespace app\common\service;


class AdminAndRoleService
{
    /**
     * 将角色ID转为角色字符串
     * @param array $roles 角色列表
     * @param array $role_ids 角色ID
     * @return array
     */
    public static function roleId2Str(array $roles, array $role_ids)
    {
        if(!$role_ids) return [];
        $str = [];
        foreach ($role_ids as $role) {
            $str[] = $roles[$role];
        }
        return $str;
    }

}
