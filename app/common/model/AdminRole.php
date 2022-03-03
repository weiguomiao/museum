<?php

namespace app\common\model;

use app\BaseModel;

class AdminRole extends BaseModel
{
    protected $json = ['auth_list','menu_list'];
    protected $jsonAssoc = true;
}
