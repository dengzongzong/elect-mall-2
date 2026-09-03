<?php

namespace Crmeb\Easypay;

use Crmeb\Easypay\Config\AbstractConfig;
use Crmeb\Easypay\Config\UnionMerConfig;
use Crmeb\Easypay\Gateway\UnionMerService;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Crmeb\Easypay\Config\CommonConfig;

/**
 * Facade
 * @package Crmeb\Easypay
 * @method UnionMerService unionmer(UnionMerConfig $config = null)
 */
class Facade
{
    /**
     * @var array
     */
    private $register = [
        'unionmer' => UnionMerService::class,
    ];

    /**
     * 驱动
     * @var array
     */
    private $drivers = [];

    /**
     * 日志
     * @var LoggerInterface
     */
    private $logger;

    /**
     * 缓存
     * @var CacheInterface
     */
    private $cache;

    /**
     * 配置
     * @var CommonConfig
     */
    private $config;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->config = new CommonConfig();
    }

    /**
     * 自动注册
     * @return void
     */
    private function autoRegister(string $name, $arguments)
    {
        if (isset($this->register[$name])) {
            $class = $this->register[$name];
            $this->drivers[$name] = new $class($this->logger, $this->cache, isset($arguments[0]) && $arguments[0] instanceof AbstractConfig ? $arguments[0] : $this->config);
        } else {
            throw new \InvalidArgumentException(sprintf(
                'Unable to resolve [%s] driver [%s].', static::class, $name
            ));
        }

        return $this->drivers[$name];
    }

    /**
     * 注册日志
     * @param LoggerInterface $logger
     * @return Facade
     */
    public function registerLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * 注册缓存
     * @param CacheInterface $cache
     * @return Facade
     */
    public function registerCache(CacheInterface $cache)
    {
        $this->cache = $cache;
        return $this;
    }

    /**
     * 注册配置
     * @param AbstractConfig $config
     * @return Facade
     */
    public function registerConfig(AbstractConfig $config)
    {
        $this->config = $config;
        return $this;
    }

    /**
     * 动态调用
     * @param $method
     * @param $arguments
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        return $this->driver($method, $arguments);
    }

    /**
     * 获取驱动实例
     * @param string $name
     * @param $arguments
     * @return mixed
     */
    protected function driver(string $name, $arguments)
    {
        if (!$name) {
            throw new \InvalidArgumentException(sprintf(
                'Unable to resolve NULL driver for [%s].', static::class
            ));
        }

        return $this->createDriver($name, $arguments);
    }

    /**
     * 创建驱动
     *
     * @param string $name
     * @param $arguments
     * @return mixed
     */
    protected function createDriver(string $name, $arguments)
    {
        if (!isset($this->drivers[$name])) {
            $this->autoRegister($name, $arguments);
        }

        return $this->drivers[$name];
    }
}