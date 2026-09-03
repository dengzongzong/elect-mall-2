<?php

namespace Crmeb\Easypay\Cache;

use Crmeb\Easypay\Exception\PayException;
use Psr\SimpleCache\CacheInterface;

/**
 * redis缓存类
 * Cache
 * @package Crmeb\Easypay
 */
class RedisCache implements CacheInterface
{

    /**
     * @var \Redis
     */
    protected $redis;

    /**
     * @var array
     */
    protected $config;

    /**
     * Cache constructor.
     * @param array $config
     * @throws PayException
     */
    public function __construct(array $config)
    {
        $this->config = $config;

        try {
            $this->redis = new \Redis;

            $this->redis->connect($config['host'], (int)$config['port'], (int)$config['timeout']);

            if (!empty($config['password'])) {
                $this->redis->auth($config['password']);
            }

            if (isset($this->config['select']) && 0 != $this->config['select']) {
                $this->redis->select($this->config['select']);
            }

        } catch (\Throwable $e) {
            throw new PayException($e->getMessage());
        }
    }

    /**
     * 序列化数据
     * @access protected
     * @param mixed $data 缓存数据
     * @return string
     */
    protected function serialize($data): string
    {
        if (is_numeric($data)) {
            return (string)$data;
        }

        $serialize = $this->config['redis']['serialize'][0] ?? "serialize";

        return $serialize($data);
    }

    /**
     * 反序列化数据
     * @access protected
     * @param string $data 缓存数据
     * @return mixed
     */
    protected function unserialize(string $data)
    {
        if (is_numeric($data)) {
            return $data;
        }

        $unserialize = $this->config['redis']['serialize'][1] ?? "unserialize";

        return $unserialize($data);
    }

    /**
     * @param string $key
     * @param null $default
     * @return int|mixed|string|null
     * @throws \RedisException
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2022/10/13
     */
    public function get($key, $default = null)
    {
        $key = $this->getCacheKey($key);
        $value = $this->unserialize($this->redis->get($key));

        return $value !== null ? $value : $default;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param int|\DateInterval|null $ttl
     * @return bool
     * @throws \RedisException
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2022/10/13
     */
    public function set($key, $value, $ttl = null): bool
    {
        $key = $this->getCacheKey($key);
        $value = $this->serialize($value);

        return $this->redis->set($key, $value, $ttl);
    }

    /**
     * 删除单个
     * @param string $key
     * @return bool
     * @throws \RedisException
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2022/10/13
     */
    public function delete($key): bool
    {
        return $this->redis->del($this->getCacheKey($key));
    }

    /**
     * 清除全部
     * @return bool
     * @throws \RedisException
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2022/10/13
     */
    public function clear(): bool
    {
        $this->redis->flushDB();
        return true;
    }

    /**
     * @param iterable $keys
     * @param null $default
     * @return array
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2022/10/13
     */
    public function getMultiple($keys, $default = null): iterable
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    /**
     * @param iterable $values
     * @param int|\DateInterval|null $ttl
     * @return bool
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2022/10/13
     */
    public function setMultiple($values, $ttl = null): bool
    {
        foreach ($values as $key => $val) {
            $result = $this->set($key, $val, $ttl);

            if (false === $result) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param iterable $keys
     * @return bool
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2022/10/13
     */
    public function deleteMultiple($keys): bool
    {
        foreach ($keys as $key) {
            $result = $this->delete($key);

            if (false === $result) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $key
     * @return bool|int
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2022/10/13
     */
    public function has($key): bool
    {
        return $this->redis->exists($this->getCacheKey($key));
    }

    /**
     * @param string $key
     * @return string
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2022/10/13
     */
    protected function getCacheKey(string $key)
    {
        return $this->config['prefix'] . $key;
    }

    /**
     * @param $method
     * @param $args
     * @return false|mixed
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2022/10/13
     */
    public function __call($method, $args)
    {
        return call_user_func_array([$this->redis, $method], $args);
    }
}
