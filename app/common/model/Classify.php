<?php
declare (strict_types = 1);

namespace app\common\model;

use app\BaseModel;
use app\common\getAttr\ImageAttr;
use think\Model;

/**
 * @mixin think\Model
 */
class Classify extends BaseModel
{
    //上传图片
    use ImageAttr;
}
