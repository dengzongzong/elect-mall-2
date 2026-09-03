<?php

namespace Crmeb\Easypay\Config;

/**
 * 银联商务支付配置
 * Class UnionMerConfig
 * @package Crmeb\Easypay
 * @property string appId
 * @property string appKey
 * @property string mchId
 * @property string tid
 * @property string notifyUrl
 * @property string returnUrl
 * @method string getAppId()
 * @method string getAppKey()
 * @method string getMchId()
 * @method string getTid()
 * @method string getNotifyUrl()
 * @method string getReturnUrl()
 * @method UnionMerConfig setAppId(string $appid)
 * @method UnionMerConfig setAppKey(string $appKey)
 * @method UnionMerConfig setMchId(string $mchId)
 * @method UnionMerConfig setTid(string $tid)
 * @method UnionMerConfig setNotifyUrl(string $notifyUrl) 设置可设置异步通知接收服务地址
 * @method UnionMerConfig setReturnUrl(string $returnUrl) 退款
 */
class UnionMerConfig extends CommonConfig
{
    /**
     * 正式环境
     * @var string
     */
    protected $baseUrl = 'https://api-mop.chinaums.com';

    /**
     * 测试环境
     * @var string
     */
    protected $devBaseUrl = 'https://test-api-open.chinaums.com';

    /**
     * 是否测试环境
     * @var bool
     */
    public $isDev = false;

    /**
     * @var string[]
     */
    protected $rule = [
        'app_id'     => '',
        'app_key'    => '',
        'mch_id'     => '',
        'tid'        => '',
        'notify_url' => '',//可设置异步通知接收服务地址支付
        'return_url' => '',//可设置异步通知接收服务地址退款
    ];
}