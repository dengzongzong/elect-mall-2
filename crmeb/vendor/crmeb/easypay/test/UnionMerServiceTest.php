<?php

use Crmeb\Easypay\Facade;
use Crmeb\Easypay\Config\UnionMerConfig;
use PHPUnit\Framework\TestCase;


class UnionMerServiceTest extends TestCase
{

    public function testScan()
    {
        date_default_timezone_set('Asia/Shanghai');

        $facade = new Facade();

        $redisConfig = new \Crmeb\Easypay\Config\RedisConfig([
            'host'     => '127.0.0.1',
            'port'     => '6379',
            'password' => '',
            'select'   => 1
        ]);

        $facade->registerCache(new \Crmeb\Easypay\Cache\RedisCache($redisConfig->toArray()));
        $facade->registerLogger(new \Crmeb\Easypay\Log\FileLogger(dirname(__DIR__) . '/logs'));

        $config = new UnionMerConfig([
            'appId'  => '8a81c1bd831e4c9601862f7e03253998',
            'appKey' => '259D48AE69D272289E2AC0E0DF72F4F8',
            'mchId'  => '898610100008164',
            'tid'    => 'KPJAGCEL'
        ]);

        $res = $facade->unionmer($config)->h5Pay('uni45457845122244545664446', '0.01', 'unionmer', '测试商品支付');

        var_dump($res);
    }
}