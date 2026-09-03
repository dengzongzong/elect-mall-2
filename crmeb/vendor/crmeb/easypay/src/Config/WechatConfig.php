<?php

namespace Crmeb\Easypay\Config;

/**
 * 微信支付配置
 * Class WechatConfig
 * @package Crmeb\Easypay
 * @property string $appid 微信公众号appid
 * @property string $mchId  商户号
 * @property string $key 密钥
 * @property string $certPath 证书路径cert
 * @property string $keyPath 密钥路径key
 * @property string $miniprogramMchid 小程序商户号
 * @property string $notifyUrl 微信支付回调地址
 * @property string $subMchId 子商户号
 * @property string $spAppid 主商户APPID
 * @method string getAppid() 获取微信公众号appid
 * @method string getMchId() 获取商户号
 * @method string getKey() 获取密钥
 * @method string getCertPath() 获取证书路径cert
 * @method string getKeyPath() 获取密钥路径key
 * @method string getMiniprogramMchid() 获取小程序商户号
 * @method string getNotifyUrl() 获取微信支付回调地址
 * @method string getSubMchId() 获取子商户号
 * @method string getSpAppid() 获取主商户APPID
 * @method WechatConfig setSubMchId(string $subMchId) 设置子商户号
 * @method WechatConfig setSpAppid(string $spAppid) 设置主商户APPID
 * @method WechatConfig setAppid(string $appid) 设置微信公众号appid
 * @method WechatConfig setMchId(string $mchId) 设置商户号
 * @method WechatConfig setKey(string $key) 设置密钥
 * @method WechatConfig setCertPath(string $certPath) 设置证书路径cert
 * @method WechatConfig setKeyPath(string $keyPath) 设置密钥路径key
 * @method WechatConfig setMiniprogramMchid(string $miniprogramMchid) 设置小程序商户号
 * @method WechatConfig setNotifyUrl(string $notifyUrl) 设置微信支付回调地址
 */
class WechatConfig extends CommonConfig
{
    /**
     * 配置项
     * @var string[]
     */
    protected $rule = [
        'appid'             => '',
        'sub_mch_id'        => '',
        'mch_id'            => '',
        'sp_appid'          => '',
        'key'               => '',
        'cert_path'         => '',
        'key_path'          => '',
        'miniprogram_mchid' => '',
        'notify_url'        => '',
    ];
}