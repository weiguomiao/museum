<?php

use think\facade\Route;

// 登录
Route::post('login', 'index/loginHandle');

// 文件异步上传
Route::post('upload', 'index/uploadFile');
Route::post('getQrCode', 'index/getQrCode');//活动报名二维码
Route::post('getSignQrCode', 'index/getSignQrCode');//活动报名二维码
Route::post('getPayQrCode', 'index/getPayQrCode');//收款二维码

// 获取各种状态
Route::post('getStatus', 'index/getStatus');

// 管理员相关
Route::group('admin', function () {
    // 获取管理员列表
    Route::post('index', 'admin/index');
    // 修改管理员状态
    Route::post('status', 'admin/setStatus');
    // 修改个人信息
    Route::post('info', 'admin/saveAdminInfo');
    // 修改密码
    Route::post('pwd', 'admin/savePwd');
    // 删除管理员
    Route::post('del', 'admin/delete');
    // 保存管理员信息
    Route::post('save', 'admin/save');
    // 获取管理员所有权限
    Route::post('auth_list', 'admin/getAdminAuthList');
});

// 角色相关
Route::group('role', function () {
    // 获取角色列表
    Route::post('index', 'adminRole/index');
    // 修改角色状态
    Route::post('status', 'adminRole/setStatus');
    // 删除角色
    Route::post('del', 'adminRole/delete');
    // 保存角色信息
    Route::post('save', 'adminRole/save');
    // 获取权限列表
    Route::post('auth_list', 'adminRole/getAuthList');
    // 刷新系统权限
    Route::post('refresh', 'adminRole/flashAuthList');
});

// 系统配置 路由
Route::group('config', function () {
    // 系统配置列表
    Route::post('index', 'config/index');
    // 编辑系统配置
    Route::post('save', 'config/update');
    // 广告图
    Route::post('banner', 'config/banner');
    //修改图片状态
    Route::post('bannerStu', 'config/bannerStu');
    // 添加广告图
    Route::post('bannerSave', 'config/bannerSave');
    // 图片排序置顶
    Route::post('bannerTop', 'config/bannerTop');
    // 删除广告图
    Route::post('bannerDel', 'config/bannerDel');
});
//藏品
Route::group('', function () {
    //藏品列表
    Route::post('goodsList', 'goods/goodsList');
    //添加修改藏品
    Route::post('addGoods', 'goods/addGoods');
    //设置商品状态
    Route::post('setGoodsStatus', 'goods/setGoodsStatus');
});
//商品
Route::group('', function () {
    //查看商品列表
    Route::post('mallList', 'mall/goodsList');
    //添加修改商品
    Route::post('addMall', 'mall/addGoods');
    //设置商品状态
    Route::post('setMallStatus', 'mall/setGoodsStatus');
    //商品置顶
    Route::post('goodsTop', 'mall/goodsTop');
    //goodsSave
    Route::post('goodsSave', 'mall/goodsSave');
    //specList
    Route::post('specList', 'mall/specList');
    //setSpec
    Route::post('setSpec', 'mall/setSpec');
    //delSpec
    Route::post('delSpec', 'mall/delSpec');
    //分类列表
    Route::post('typeList', 'mall/typeList');
    //添加编辑分类
    Route::post('addType', 'mall/addType');
    //修改分类状态
    Route::post('setTypeStatus', 'mall/setTypeStatus');

});
//订单
Route::group('', function () {
    Route::post('orderList', 'order/orderList');//查看订单列表
    Route::post('addPost', 'order/addPost');//发货添加修改快递单号
    Route::rule('export', 'order/export');//导出excel表
    Route::rule('postList', 'order/postList');//物流公司列表
    Route::post('postInfo', 'order/postInfo');//物流查询
});

//充值卡管理
Route::group('', function () {
    Route::post('cardList', 'card/cardList');//展示充值卡
//    Route::post('addCard', 'card/addCard');//生成充值卡
//    Route::post('delCard', 'card/delCard');//删除充值卡
    Route::rule('cardExcel', 'card/cardExcel');//导出充值卡表格
//    Route::post('setCardStatus', 'card/setCardStatus');//充值卡激活
//    Route::post('manyAct', 'card/manyAct');//批量激活
    Route::post('rechargeList', 'card/rechargeList');//用户充值赠送列表
    Route::post('addRecharge', 'card/addRecharge');//添加修改充值赠送
    Route::post('delRecharge', 'card/delRecharge');//删除充值赠送
    Route::post('addCardBatch', 'card/addCardBatch');//添加卡
    Route::post('getCardType', 'card/getCardType');//获取卡类型
    Route::post('cardBatch', 'card/cardBatch');//卡批次
    Route::post('activationLog', 'card/activationLog');//激活记录
    Route::post('cardSave', 'card/cardSave');//卡编辑
    Route::post('activation','card/activation');//批次激活
});
//用户
Route::group('', function () {
    Route::post('userList', 'user/userList');//用户列表
    Route::post('userStatus', 'user/status');//修改用户状态
    Route::post('setLevel', 'user/setLevel');//设置用户等级
    Route::post('balanceLog', 'user/balanceLog');//查看用户余额记录
    Route::post('actLog', 'user/actLog');//查看用户报名活动记录
    Route::post('bookLog', 'user/bookLog');//查看用户预约记录
    Route::post('rechargeLog', 'user/rechargeLog');//查看用户充值记录
    Route::post('qrcodeList', 'user/qrcodeList');//收款二维码列表
    Route::post('addQrcode', 'user/addQrcode');//用户关联二维码
    Route::post('setQrcodeStatus', 'user/setQrcodeStatus');//修改二维码状态
    Route::post('smpayRecord', 'user/smpayRecord');//扫码收款记录
    Route::rule('PayExcel','user/PayExcel');//导出账单
});
//活动管理
Route::group('', function () {
    Route::post('activityList', 'activity/activityList');//活动列表
    Route::post('addActivity', 'activity/addActivity');//添加修改活动
    Route::post('setActStatus', 'activity/setActStatus');//修改活动状态
    Route::post('bookList', 'activity/bookList');//预约平台列表
    Route::post('bookInfo', 'activity/bookInfo');//预约平台详情
    Route::post('setBook', 'activity/setBook');//修改预约平台
    Route::post('activityLog', 'activity/actLog');//活动报名记录
    Route::post('bookPtLog', 'activity/bookLog');//预约平台记录
    Route::post('setBookStatus', 'activity/setBookStatus');//平台状态
});

Route::group('',function (){
    Route::post('ticketList','ticketList');//门票列表
    Route::post('setTicketStatus','setTicketStatus');//设置门票状态
    Route::post('ticketSave','ticketSave');//门票保存
    Route::post('ticketOrderList','ticketOrderList');//门票订单
    Route::post('guide','guide');//购票须知和收费标准
    Route::post('guideSave','guideSave');//编辑购票须知和收费标准

    Route::post('calenderList','calenderList');//日历列表
    Route::post('calenderSave','calenderSave');//编辑日历
    Route::post('delCalender','delCalender');//刪除日历
})->prefix('ticket/');
