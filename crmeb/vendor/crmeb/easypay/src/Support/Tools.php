<?php

namespace Crmeb\Easypay\Support;

/**
 * Class Tools
 */
class Tools
{
    /**
     * 获取时间
     * @param string $tag
     * @return array|string|string[]
     */
    public static function getTime(string $tag)
    {
        [$usec, $sec] = explode(" ", microtime());

        $nowTime = ((float)$usec + (float)$sec);

        [$usec, $sec] = explode(".", $nowTime);
        $date = date($tag, $usec);

        return str_replace('x', $sec, $date);
    }

    /**
     * 生成uuid
     * @return string
     */
    public static function guid()
    {
        mt_srand((int)microtime() * 10000);
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid = chr(123)// "{"
            . substr($charid, 0, 8) . $hyphen
            . substr($charid, 8, 4) . $hyphen
            . substr($charid, 12, 4) . $hyphen
            . substr($charid, 16, 4) . $hyphen
            . substr($charid, 20, 12)
            . chr(125);// "}"
        return $uuid;
    }

    /**
     * 创建uuid
     * @param string $prefix
     * @return string
     */
    public static function createUuid(string $prefix = "")
    {    //可以指定前缀
        $str = md5(uniqid(mt_rand(), true));
        $uuid = substr($str, 0, 8) . '-';
        $uuid .= substr($str, 8, 4) . '-';
        $uuid .= substr($str, 12, 4) . '-';
        $uuid .= substr($str, 16, 4) . '-';
        $uuid .= substr($str, 20, 12);
        return $prefix . $uuid;
    }

    /**
     * hmacSHA256
     * @param string $content
     * @param string $key
     * @return string
     */
    public static function hmacSHA256(string $content, string $key)
    {
        return hash_hmac('sha256', $content, $key);
    }

    /**
     * sha256Hex
     * @param string $content
     * @return string
     */
    public static function sha256Hex(string $content)
    {
        return hash('sha256', $content);
    }
}