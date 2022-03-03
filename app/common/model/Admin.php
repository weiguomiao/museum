<?php

namespace app\common\model;

use app\BaseModel;
use app\common\getAttr\ImageAttr;

class Admin extends BaseModel
{
    use ImageAttr;

    protected $json = ['role_id'];
    protected $jsonAssoc = true;
}
