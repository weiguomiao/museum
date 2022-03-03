<?php
// 应用公共文件

if(!function_exists('dd')) {
    // 中断执行并打印
    function dd(... $params) {
        dump(... $params);
        die;
    }
}

if(!function_exists('getModelConstArray')) {
    // 将常量值转换为索引数组
    function getModelConstArray($const) {
        return array_values($const);
    }
}

// 过滤微信昵称
function filterNickname($nickname)
{
    $nickname = preg_replace('/[\x{1F600}-\x{1F64F}]/u', '', $nickname);
    $nickname = preg_replace('/[\x{1F300}-\x{1F5FF}]/u', '', $nickname);
    $nickname = preg_replace('/[\x{1F680}-\x{1F6FF}]/u', '', $nickname);
    $nickname = preg_replace('/[\x{2600}-\x{26FF}]/u', '', $nickname);
    $nickname = preg_replace('/[\x{2700}-\x{27BF}]/u', '', $nickname);
    $nickname = str_replace(array('"', '\''), '', $nickname);
    return addslashes(trim($nickname)) ? addslashes(trim($nickname)) : '用户_' . mt_rand(10000, 99999);
}

function getWeek($date_time)
{
    $weekarray = ["星期日", "星期一", "星期二", "星期三", "星期四", "星期五", "星期六"];
    $xingqi = date("w", $date_time);
    return $weekarray[$xingqi];
}