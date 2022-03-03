<?php
declare (strict_types = 1);

namespace app\common\model;

use app\BaseModel;
use think\Model;
use think\model\concern\SoftDelete;

/**
 * @mixin think\Model
 */
class UserAddress extends BaseModel
{
    protected $pk='id';
}
