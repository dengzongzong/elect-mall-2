<?php

namespace Crmeb\Easypay\Config;

/**
 * 公共配置
 * Class CommonConfig
 * @package Crmeb\Easypay
 * @property bool $logger 日志开关
 * @property int $httpTimeout 请求超时时间
 * @property bool $httpSsl 是否使用ssl
 * @property array $httpSslCert ssl证书
 * @property string|bool $httpVerify SSL certificate
 * @method CommonConfig setLogger(bool $logger) 设置日志开关
 * @method CommonConfig setHttpTimeout(int $httpTimeout) 设置请求超时
 * @method CommonConfig setHttpSsl(bool $httpSsl) 设置是否使用ssl
 * @method CommonConfig setHttpProxy(string $httpProxy) 设置请求代理
 * @method CommonConfig setHttpVerify(string|bool $httpVerify) SSL certificate
 * @method bool getLogger() 获取日志开关
 * @method int getHttpTimeout() 获取请求超时
 * @method bool getHttpSsl() 获取是否使用ssl
 * @method string getHttpProxy() 获取请求代理
 * @method string|bool getHttpVerify() SSL certificate
 */
class CommonConfig extends AbstractConfig
{

    /**
     * 正式环境
     * @var string
     */
    protected $baseUrl = '';

    /**
     * 测试环境
     * @var string
     */
    protected $devBaseUrl = '';

    /**
     * 是否测试环境
     * @var bool
     */
    public $isDev = false;

    /**
     * @var array
     */
    protected $common = [
        'logger'       => true,
        'http_timeout' => 10,
        'http_ssl'     => false,
        'http_proxy'   => '',
        'http_verify'  => false,
    ];

    /**
     * CommonConfig constructor.
     */
    public function __construct(array $config = [])
    {
        $this->rule = array_merge($this->rule, $this->common);
        parent::__construct($config);
    }

    /**
     * 获取baseUrl
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * 获取baseUri
     * @return string
     */
    public function getBaseUri()
    {
        return $this->isDev ? $this->devBaseUrl : $this->baseUrl;
    }

    /**
     * 获取测试环境baseUrl
     * @return string
     */
    public function getDevBaseUrl()
    {
        return $this->devBaseUrl;
    }
}