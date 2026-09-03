<?php

namespace Crmeb\Easypay\Support;


class Str
{
    /**
     * @param string $haystack
     * @param $needles
     * @return bool
     */
    public static function endsWith(string $haystack, $needles)
    {
        foreach ((array)$needles as $needle) {
            if (substr($haystack, -strlen($needle)) === (string)$needle) {
                return true;
            }
        }
        return false;
    }

    /**
     *
     * @param string $haystack
     * @param $needles
     * @return bool
     */
    public static function startsWith(string $haystack, $needles)
    {
        foreach ((array)$needles as $needle) {
            if ('' !== $needle && substr($haystack, 0, strlen($needle)) === (string)$needle) {
                return true;
            }
        }

        return false;
    }

    /**
     * 获取字符串长度
     * @param string $value
     * @return string
     */
    public static function lower(string $value): string
    {
        return mb_strtolower($value, 'UTF-8');
    }

    /**
     * 驼峰转下划线
     * @param string $value
     * @param string $delimiter
     * @return string
     */
    public static function snake(string $value, string $delimiter = '_'): string
    {
        if (!ctype_lower($value)) {
            $value = preg_replace('/\s+/u', '', ucwords($value));

            $value = self::lower(preg_replace('/(.)(?=[A-Z])/u', '$1' . $delimiter, $value));
        }

        return $value;
    }
}