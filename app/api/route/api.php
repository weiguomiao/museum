<?php
use think\facade\Route;

//藏品详情
Route::rule('goodsInfo','goods/goodsInfo');

Route::rule('login','index/login');

Route::rule('msgServer','index/msgServer');

Route::rule('wxJsSdk','index/wxJsSdk');
Route::rule('wxRedirect','index/wxRedirect');
//首页
Route::group('goods',function (){
    Route::rule('home','goods/index');//商城首页
    Route::post('goodsList','goods/goodsList');//商品列表
    Route::rule('cidList','goods/cidList');//分类列表
    Route::post('classify','goods/classify');//分类
    Route::post('goodsDetail','goods/goodsDetail');//商品详情
})->middleware(\app\api\middleware\ApiTokenMiddleware::class);

Route::rule('notify','notify/notify');//支付回调
Route::rule('refundNotify','refundNotify/notify');//退款回调

//预约须知和收费标准
Route::rule('ticket/guide','ticket/guide');

Route::group('',function (){
    //订单
    Route::group('order',function (){
        Route::post('orderDisplay','order/orderDisplay');//订单结算
        Route::post('createOrder','order/createOrder');//创建订单
        Route::post('pay','order/pay');//支付
        Route::post('orderList','order/orderList');//订单列表
        Route::post('orderInfo','order/orderInfo');//订单详情
        Route::post('orderPost','order/orderPost');//查询物流
        Route::post('confirm','order/confirm');//确认收货
        Route::post('orderCancel','order/orderCancel');//订单取消
    });
    Route::post('getStatus','notify/getStatus'); //获取订单状态
    //用户
    Route::group('user',function (){
        Route::post('getUserAddress', 'user/getUserAddress');//获取收货地址
        Route::post('saveUserAddress', 'user/saveUserAddress');//添加修改收货地址
        Route::post('addressInfo','user/addressInfo');//地址详情
        Route::post('delUserAddress', 'user/delUserAddress');//删除收货地址

        Route::post('userCenter','user/userCenter');//用户中心
        Route::post('rechargeList','user/rechargeList');//展示充值列表
        Route::post('recharge','user/recharge');//微信充值订单
        Route::post('cardRecharge','user/cardRecharge');//卡充值

        Route::post('balanceLog','user/balanceLog');//余额变动记录
        Route::post('buyLog','user/buyLog');//消费记录
        Route::post('bookLog','user/bookLog');//预约记录
        Route::post('actLog','user/actLog');//报名记录
    });

    //扫码
    Route::group('',function (){
        Route::rule('smPay','saoma/smPay');//扫码支付
        Route::rule('sign','saoma/sign');//扫码签到
        Route::rule('payRecordLog','saoma/payRecordLog');//线下扫码收款记录
    });

    //活动预约
    Route::group('',function (){
        Route::rule('actShow','activity/actShow');//活动展示
        Route::rule('actSign','activity/actSign');//活动报名
        Route::rule('bookPtShow','activity/bookPtShow');//预约平台展示
        Route::rule('submitBook','activity/submitBook');//预约平台提交
        Route::rule('bookShow','activity/bookShow');//预约展示
        Route::rule('addBookPt','activity/addBookPt');//报名预约
        Route::rule('bookLogInfo','activity/bookLogInfo');//预约详情
        Route::rule('activityInfo','activity/activityInfo');//
        Route::rule('bookInfo','activity/bookInfo');//
    });

    //门票
    Route::group('ticket',function (){
        Route::rule('ticketList','ticket/ticketList');//门票列表
        Route::rule('ticketInfo','ticket/ticketInfo');//门票信息
        Route::rule('calendarChoice','ticket/calendarChoice');//选择日期
        Route::rule('createOrder','ticket/createOrder');//创建订单
        Route::rule('orderList','ticket/orderList');//订单列表
        Route::rule('orderInfo','ticket/orderInfo');//订单详情
        Route::rule('updateTime','ticket/updateTime');//修改预约时间
        Route::rule('useOrderList','ticket/useOrderList');//核销列表
        Route::rule('refund','ticket/refund');//退款

    });
})->middleware(\app\api\middleware\ApiTokenMiddleware::class);
