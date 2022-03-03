<?php
declare (strict_types = 1);

namespace app\common\model;

use app\BaseModel;
use think\Model;

/**
 * @mixin think\Model
 */
class BookPt extends BaseModel
{
    //剩余预约人数
    public function getSurplusAttr($v,$d){
        return $d['number']-$d['book_num'];
    }

    //日期
    public function getDateValAttr($v,$d){
        return date('m-d',$d['time']);
    }

    //星期
    public function getWeekValAttr($v,$d){
        return getWeek($d['time']);
    }

    //年份
    public function getYearValAttr($v,$d){
        return date('Y',$d['time']);
    }
}
