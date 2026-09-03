<?php

namespace Crmeb\Easypay\Config;

/**
 * 微信V3配置
 * Class WechatV3Config
 * @package Crmeb\Easypay
 * @property string $appid 公众号appid
 * @property string $spAppid 主商户APPID
 * @property string $mchId 商户号
 * @property string $subMchId 子商户号
 * @property string $certPath 证书路径cert
 * @property string $keyPath 密钥路径key
 * @property string $serialNo 证书序列号
 * @property array $publicCertPath 公钥证书，可以配置为 证书序列号=>证书路径
 * @property string $notifyUrl 微信回调地址
 * @method WechatV3Config setAppid(string $appid) 设置公众号appid
 * @method WechatV3Config setSpAppid(string $spAppid) 设置主商户APPID
 * @method WechatV3Config setMchId(string $mchId) 设置商户号
 * @method WechatV3Config setSubMchId(string $subMchId) 设置子商户号
 * @method WechatV3Config setCertPath(string $certPath) 设置证书路径cert
 * @method WechatV3Config setKeyPath(string $keyPath) 设置密钥路径key
 * @method WechatV3Config setSerialNo(string $serialNo) 获取证书序列号
 * @method WechatV3Config setPublicCertPath(array $publicCertPath) 获取公钥证书，可以配置为 证书序列号 => 证书路径
 * @method WechatV3Config setNotifyUrl(string $notifyUrl) 设置微信回调地址
 * @method WechatV3Config getAppid() 获取公众号appid
 * @method WechatV3Config getSpAppid() 获取主商户APPID
 * @method WechatV3Config getMchId() 获取商户号
 * @method WechatV3Config getSubMchId() 获取子商户号
 * @method WechatV3Config getCertPath() 获取证书路径cert
 * @method WechatV3Config getKeyPath() 获取密钥路径key
 * @method WechatV3Config getSerialNo() 获取证书序列号
 * @method WechatV3Config getPublicCertPath() 获取公钥证书，可以配置为 证书序列号 => 证书路径
 * @method WechatV3Config getNotifyUrl() 获取微信回调地址
 */
class WechatV3Config extends CommonConfig
{

    /**
     * 配置项
     * @var string[]
     */
    protected $rule = [
        'appid'            => '',
        'sub_mch_id'       => '',
        'mch_id'           => '',
        'sp_appid'         => '',
        'cert_path'        => '',
        'key_path'         => '',
        'serial_no'        => '',
        'public_cert_path' => [
            '' => ''
        ],
        'notify_url'       => ''
    ];
}