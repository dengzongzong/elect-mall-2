<?php

namespace Crmeb\Easypay\Gateway\UnionMer;

use Crmeb\Easypay\Exception\PayException;
use Crmeb\Easypay\Exception\PayResponseException;
use Crmeb\Easypay\Config\UnionMerConfig;
use Crmeb\Easypay\Enum\PayUnionMerEnum;
use Crmeb\Easypay\Gateway\AbstractPay;
use Crmeb\Easypay\Support\Tools;
use GuzzleHttp\Exception\GuzzleException;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Class Support
 * @package Crmeb\Gateway\UnionMer
 */
class Support
{
    /**
     *
     * @var UnionMerConfig
     */
    private $config;

    /**
     *
     * @var AbstractPay
     */
    private $abstractPay;


    /**
     *
     * @param AbstractPay $abstractPay
     */
    public function __construct(AbstractPay $abstractPay)
    {
        $this->abstractPay = $abstractPay;
        $this->config = $abstractPay->getConfig();
    }

    /**
     * 获取token
     * @return mixed
     * @throws PayException
     * @throws GuzzleException
     */
    protected function getToken()
    {
        $timestamp = date("YmdHis", time());
        $nonce = Tools::createUuid();
        $signatureAttr = [
            $this->config->getAppId(),
            $timestamp,
            $nonce,
            $this->config->getAppKey(),
        ];

        $body = [
            'appId'     => $this->config->getAppId(),
            'appKey'    => $this->config->getAppKey(),
            'timestamp' => $timestamp,
            'nonce'     => $nonce,
            'signature' => sha1(implode('', $signatureAttr)),
        ];

        $response = $this->abstractPay->jsonSendRequest(PayUnionMerEnum::TOKEN_API_URL, 'post', $body);

        if ($response['errCode'] == '0000' && isset($response['accessToken'])) {
            return $response['accessToken'];
        } else {
            throw new PayResponseException('获取token失败：' . ($response['errMsg'] ?? ''), 0, null, $response);
        }
    }

    /**
     * @return mixed
     * @throws PayException
     * @throws GuzzleException
     * @throws InvalidArgumentException
     */
    protected function getCacheToken()
    {
        $key = $this->config->getAppId() . '_UNIONMER_TOKEN';
        if ($this->abstractPay->getCache()->has($key)) {
            $accessToken = $this->abstractPay->getCache()->get($key);
        } else {
            $accessToken = $this->getToken();
            $this->abstractPay->getCache()->set($key, $accessToken, 3600);
        }

        return $accessToken;
    }

    /**
     * 发送json请求
     * @param string $url
     * @param array $data
     * @param bool $isToken
     * @return array
     * @throws PayException
     * @throws GuzzleException
     * @throws InvalidArgumentException|PayResponseException
     */
    public function jsonSendRequest(string $url, array $data = [], bool $isToken = true)
    {
        $data = array_filter($data, function ($value) {
            return '' !== $value && !is_null($value);
        });

        $headers = [];

        if ($isToken) {
            $accessToken = $this->getCacheToken();
            $headers = [
                'Authorization' => 'OPEN-ACCESS-TOKEN AccessToken=' . $accessToken,
            ];
        }

        $response = $this->abstractPay->jsonSendRequest($url, 'post', $data, $headers);

        if ($response['errCode'] == 'SUCCESS') {
            return $response;
        } else {
            throw new PayResponseException('请求失败：' . ($response['errMsg'] ?? ''), 0, null, $response);
        }
    }

    /**
     * 组合查询参数返回请求地址
     * @param string $url
     * @param array $query
     * @return string
     */
    public function querySendRequest(string $url, array $query)
    {
        $query = array_filter($query, function ($value) {
            return '' !== $value && !is_null($value);
        });

        $jsonQuery = json_encode($query);
        $timestamp = date("YmdHis");
        $nonce = Tools::createUuid();

        $signature = [
            $this->config->getAppId(),
            $timestamp,
            $nonce,
            Tools::sha256Hex($jsonQuery)
        ];

        $hmacSHA256 = Tools::hmacSHA256(implode('', $signature), $this->config->getAppKey());

        $urlParamsAttr = [
            'authorization' => 'OPEN-FORM-PARAM',
            'appId'         => $this->config->getAppId(),
            'timestamp'     => $timestamp,
            'nonce'         => $nonce,
            'content'       => urlencode($jsonQuery),
            'signature'     => base64_encode($hmacSHA256)
        ];

        return $this->config->getBaseUri() . $url . '?' . http_build_query($urlParamsAttr);
    }
}