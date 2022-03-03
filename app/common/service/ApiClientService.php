<?php


namespace app\common\service;

use app\common\exception\AppRuntimeException;
use mytools\lib\Uapi;

/**
 * 自用接口服务层
 * Class SafetyService
 * @package app\common\service
 */
class ApiClientService
{
    /**
     * 发送请求
     * @param string $uri
     * @param array $data
     * @return array
     * @throws \think\db\exception\DbException
     * @throws AppRuntimeException
     */
    private function send(string $uri, array $data)
    {
        $res = Uapi::send('apis/api/' . $uri, $data);

        if ($res['status'] == 0) {
            throw new AppRuntimeException($res['msg']);
        }
        return $res['data'];
    }

    /**
     * 发送短信
     * @param string $mobile
     * @param string $content
     * @throws AppRuntimeException
     * @throws \think\db\exception\DbException
     */
    public function sendMsg(string $mobile, string $content)
    {
        $data['mobile'] = $mobile;
        $data['content'] = config('conf.sms_code_template') . $content;
        $this->send('sms', $data);
    }

    /**
     * 产生短信验证码
     * @param string $mobile
     * @return string
     */
    public function getSmsCode(string $mobile)
    {
        $sms_code = mt_rand(1000, 9999);
        // 将验证码存入缓存
        cache(config('cache.app_cache_prefix.sms_code') . $mobile, $sms_code, 300);
        return $sms_code;
    }

    /**
     * 校验短信验证码
     * @param string $mobile
     * @param string $sms_code
     * @return bool
     * @throws AppRuntimeException
     * @throws \think\db\exception\DbException
     */
    public function checkSmsCode(string $mobile, string $sms_code)
    {
        $prefix = config('cache.app_cache_prefix.sms_code');

        $code = cache($prefix . $mobile);
        if (!$code) {
            throw new AppRuntimeException('短信验证码已失效');
        }
        if ($code != $sms_code) {
            throw new AppRuntimeException('短信验证码错误');
        }
        //让短信验证码失效
        cache($prefix . $mobile, null);
        return true;
    }

    /**
     * 实名认证
     * @param string $name
     * @param string $idcard
     * @return array
     * @throws AppRuntimeException
     * @throws \think\db\exception\DbException
     */
    public function realName(string $name, string $idcard)
    {
        $data['name'] = $name;
        $data['cardno'] = $idcard;
        return $this->send('idcard', $data);
    }

    /**
     * 银行卡三要素认证
     * @param string $bank_card
     * @param string $name
     * @param string $idcard
     * @return array
     * @throws AppRuntimeException
     * @throws \think\db\exception\DbException
     */
    public function checkBankCard3(string $bank_card, string $name, string $idcard): array
    {

        $data['accountNo'] = $bank_card;
        $data['idCard'] = $idcard;
        $data['name'] = $name;
        $send = $this->send('bank3Check', $data);
        if ($send['status'] != '01') {
            throw new AppRuntimeException($send['msg']);
        }
        return $send;
    }

    /**
     * 银行卡四要素认证
     * @param string $bank_card
     * @param string $name
     * @param string $idcard
     * @param string $mobile
     * @return array
     * @throws AppRuntimeException
     * @throws \think\db\exception\DbException
     */
    public function checkBankCard4(string $bank_card, string $name, string $idcard, string $mobile): array
    {
        $data['accountNo'] = $bank_card;
        $data['idCard'] = $idcard;
        $data['name'] = $name;
        $data['mobile'] = $mobile;
        $send = $this->send('bank4Check', $data);
        if ($send['status'] != '01') {
            throw new AppRuntimeException($send['msg']);
        }
        return $send;
    }

    /**
     * 查询物流信息
     * @param string $no 单号
     * @param string $type 物流公司代码
     * @return array
     * @throws AppRuntimeException
     * @throws \think\db\exception\DbException
     */
    public function postInfo(string $no, string $type): array
    {
        $data['no'] = $no;
        $data['type'] = $type;
        return $this->send('express', $data);
    }

    /**
     * 银行卡信息查询
     * @param string $card
     * @return array
     * @throws AppRuntimeException
     * @throws \think\db\exception\DbException
     */
    public function bankCardInfo(string $card): array
    {
        $data['bankcard'] = $card;
        return $this->send('bankCardInfo', $data);
    }

    /**
     * 根据银行代码获取总行联行号
     * @param string $code
     * @return array
     * @throws AppRuntimeException
     * @throws \think\db\exception\DbException
     */
    public function card2Aps(string $code): array
    {
        $data['bankcode'] = $code;
        return $this->send('codeToAps', $data);
    }
}