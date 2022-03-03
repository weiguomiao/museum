<?php
declare (strict_types = 1);

namespace app\h5\controller;

use app\BaseController;
use app\Request;
use EasyWeChat\Factory;

class IndexController extends BaseController
{
    public function qrcode(Request $request)
    {
        $id=$request->param('id');
        if(empty($id))return self::error('ID非法');
        return redirect('http://huananmuseum.sdream.top/view/#/pages/index/index?id='.$id);
    }
}
