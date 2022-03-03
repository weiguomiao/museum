<?php
/**
 * Created by PhpStorm.
 * User: cs
 * Date: 2019/4/29
 * Time: 9:47
 */

namespace app\common\getAttr;

/**
 * Trait IsEnableAttr  状态获取器
 * @package app\common\getAttr
 */
Trait StatusAttr
{
    /**
     * 状态值获取器
     * @param $v
     * @param $data
     * @return mixed|null
     */
    public function getStatusValAttr($v, $data)
    {
        return static::getVal(static::STATUS, $data['status']);
    }

    /**
     * 状态值修改器
     * @param $v
     * @return mixed
     */
    public function setStatusAttr($v)
    {
        return static::getIndex(static::STATUS, $v) ?? $v;
    }
}