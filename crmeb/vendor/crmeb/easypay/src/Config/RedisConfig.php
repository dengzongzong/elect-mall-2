<?php

namespace Crmeb\Easypay\Config;

/**
 * RedisConfig
 * @package Crmeb\Easypay
 * @property string $host 主机
 * @property int $port 端口
 * @property string $password 密码
 * @property int $select 选择数据库
 * @property int $timeout 超时时间
 * @property string $prefix 前缀
 * @property array $serialize 序列化方法
 * @method string getHost() 获取主机
 * @method int getPort() 获取端口
 * @method string getPassword() 获取密码
 * @method int getSelect() 获取选择数据库
 * @method int getTimeout() 获取超时时间
 * @method string getPrefix() 获取前缀
 * @method array getSerialize() 获取序列化方法
 * @method RedisConfig setHost(string $host) 设置主机
 * @method RedisConfig setPort(int $port) 设置端口
 * @method RedisConfig setPassword(string $password) 设置密码
 * @method RedisConfig setSelect(int $select) 设置选择数据库
 * @method RedisConfig setTimeout(int $timeout) 设置超时时间
 * @method RedisConfig setPrefix(string $prefix) 设置前缀
 * @method RedisConfig setSerialize(array $serialize) 设置序列化方法
 */
class RedisConfig extends AbstractConfig
{

    /**
     * 配置项
     * @var string[]
     */
    protected $rule = [
        'host'      => '127.0.0.1',
        'port'      => '6379',
        'password'  => '',
        'select'    => 1,
        'timeout'   => 60,
        'prefix'    => 'crmeb_easypay',
        'serialize' => [
            'serialize',
            'unserialize'
        ]
    ];
}