<?php

namespace Crmeb\Easypay\Config;

/**
 * 支付宝配置
 * Class AlipayConfig
 * @property string $appid 应用id
 * @property string $privateKey 应用私钥
 * @property string $publicKey 支付宝公钥
 * @property string $notifyUrl 可设置异步通知接收服务地址
 * @property string $returnUrl 可设置异步通知接收服务地址退款
 * @property string $encryptKey 可设置AES密钥，调用AES加解密相关接口时需要（可选）
 * @property string $certPath 支付宝证书路径(可选)
 * @property string $rootCertPath 支付宝根证书路径(可选)
 * @property string $merchantCertPath 商户证书路径(可选)
 * @method string getAppid() 应用id
 * @method string getPrivateKey() 应用私钥
 * @method string getPublicKey() 支付宝公钥
 * @method string getNotifyUrl() 可设置异步通知接收服务地址
 * @method string getReturnUrl() 可设置异步通知接收服务地址退款
 * @method string getEncryptKey() 可设置AES密钥，调用AES加解密相关接口时需要（可选）
 * @method string getCertPath() 支付宝证书路径/可选
 * @method string getRootCertPath() 支付宝根证书路径/可选
 * @method string getMerchantCertPath() 商户证书路径/可选
 * @method AlipayConfig setAppid(string $appid) 设置应用id
 * @method AlipayConfig setPrivateKey(string $privateKey) 设置应用私钥
 * @method AlipayConfig setPublicKey(string $publicKey) 设置支付宝公钥
 * @method AlipayConfig setNotifyUrl(string $notifyUrl) 设置可设置异步通知接收服务地址
 * @method AlipayConfig setReturnUrl(string $returnUrl) 退款
 * @method AlipayConfig setEncryptKey(string $encryptKey) 设置可设置AES密钥，调用AES加解密相关接口时需要（可选）
 * @method AlipayConfig setCertPath(string $certPath) 设置支付宝证书路径/可选
 * @method AlipayConfig setRootCertPath(string $rootCertPath) 设置支付宝根证书路径/可选
 * @method AlipayConfig setMerchantCertPath(string $merchantCertPath) 设置商户证书路径/可选
 */
class AlipayConfig extends CommonConfig
{

    /**
     * 正式环境
     * @var string
     */
    protected $baseUrl = 'https://openapi.alipay.com/gateway.do?charset=utf-8';

    /**
     * 测试环境
     * @var string
     */
    protected $devBaseUrl = 'https://openapi-sandbox.dl.alipaydev.com/gateway.do?charset=utf-8';

    /**
     * 是否测试环境
     * @var bool
     */
    public $isDev = false;

    /**
     * @var array
     */
    protected $rule = [
        'appid'              => '',
        'private_key'        => '',//应用私钥
        'public_key'         => '',//支付宝公钥
        'notify_url'         => '',//可设置异步通知接收服务地址支付
        'return_url'         => '',//可设置异步通知接收服务地址退款
        'encrypt_key'        => '',//可设置AES密钥，调用AES加解密相关接口时需要（可选）
        'cert_path'          => '',//支付宝证书路径(可选)
        'root_cert_path'     => '',//支付宝根证书路径(可选)
        'merchant_cert_path' => '',//商户证书路径(可选)
    ];
}