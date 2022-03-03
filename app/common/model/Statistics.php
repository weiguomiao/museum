<?php

namespace app\common\model;

use app\BaseModel;

class Statistics extends BaseModel
{
    protected $pk = ['who','when','what','type'];
    protected $json = ['extend'];
    protected $jsonAssoc = true;
}
