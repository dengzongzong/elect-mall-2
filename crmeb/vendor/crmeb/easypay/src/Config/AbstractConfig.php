<?php

namespace Crmeb\Easypay\Config;

use Crmeb\Easypay\Exception\AbstractException;
use Crmeb\Easypay\Support\Str;

/**
 * 抽象配置类
 * Class AbstractConfig
 */
abstract class AbstractConfig
{

    /**
     * 属性验证规则
     * @var array
     */
    protected $rule = [];

    /**
     * 构造函数
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        if ($config) {
            foreach ($config as $key => $value) {
                $key = Str::snake($key);
                if (isset($this->rule[$key])) {
                    $this->$key = $value;
                }
            }
        }
    }

    /**
     * 设置属性
     * @param string $name
     * @param $value
     * @return $this
     * @throws AbstractException
     */
    public function __set(string $name, $value)
    {
        $name = Str::snake($name);
        if (isset($this->rule[$name])) {
            $this->$name = $value;
            return $this;
        }
        throw new AbstractException('设置的属性不存在');
    }

    /**
     * 获取属性
     * @param string $name
     * @return mixed
     */
    public function __get(string $name)
    {
        $name = Str::snake($name);
        return $this->$name;
    }

    /**
     * 获取属性
     * @param string $name
     * @param array $arguments
     * @return $this|mixed
     * @throws AbstractException
     */
    public function __call(string $name, array $arguments)
    {
        $act = substr($name, 0, 3);
        $name = substr($name, 3);
        $name = Str::snake($name);
        if ($act === "get") {
            return !property_exists($this, $name) ? $this->rule[$name] : $this->$name;
        } else if ($act === "set") {
            $this->$name = $arguments[0];
            return $this;
        } else {
            throw new AbstractException('访问的方法不存在');
        }
    }

    /**
     * 转换成数组
     * @return array
     */
    public function toArray()
    {
        $data = [];
        foreach ($this->rule as $key => $value) {
            $data[$key] = !property_exists($this, $key) || is_null($this->$key) ? $value : $this->$key;
        }

        return $data;
    }

    /**
     * 转换成json
     * @return false|string
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }
}