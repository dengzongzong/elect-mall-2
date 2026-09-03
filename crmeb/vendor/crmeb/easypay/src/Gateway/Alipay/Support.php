<?php

namespace Crmeb\Easypay\Gateway\Alipay;

use Crmeb\Easypay\Config\AlipayConfig;
use Crmeb\Easypay\Exception\InvalidConfigException;
use Crmeb\Easypay\Exception\InvalidSignException;
use Crmeb\Easypay\Exception\PayException;
use Crmeb\Easypay\Exception\PayResponseException;
use Crmeb\Easypay\Gateway\AbstractPay;
use Crmeb\Easypay\Support\Str;

/**
 * Alipay Support.
 *
 */
class Support
{

    /**
     *
     * @var AlipayConfig
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
     * Generate sign.
     *
     * @throws InvalidConfigException
     *
     */
    public function generateSign(array $params): string
    {
        $privateKey = $this->config->getPrivateKey();

        if (is_null($privateKey)) {
            throw new InvalidConfigException('Missing Alipay Config -- [private_key]');
        }

        if (Str::endsWith($privateKey, '.pem')) {
            $privateKey = openssl_pkey_get_private(
                Str::startsWith($privateKey, 'file://') ? $privateKey : 'file://' . $privateKey
            );
        } else {
            $privateKey = "-----BEGIN RSA PRIVATE KEY-----\n" .
                wordwrap($privateKey, 64, "\n", true) .
                "\n-----END RSA PRIVATE KEY-----";
        }

        openssl_sign($this->getSignContent($params), $sign, $privateKey, OPENSSL_ALGO_SHA256);

        $sign = base64_encode($sign);

        $this->abstractPay->logger('Alipay Generate Sign {0} {1}', [$params, $sign]);

        if (is_resource($privateKey)) {
            openssl_free_key($privateKey);
        }

        return $sign;
    }

    /**
     * Verify sign.
     * @param bool $sync
     * @param string|null $sign
     * @throws InvalidConfigException
     *
     */
    public function verifySign(array $data, $sync = false, $sign = null): bool
    {
        $publicKey = $this->config->getPublicKey();

        if (is_null($publicKey)) {
            throw new InvalidConfigException('Missing Alipay Config -- [ali_public_key]');
        }

        if (Str::endsWith($publicKey, '.crt')) {
            $publicKey = file_get_contents($publicKey);
        } elseif (Str::endsWith($publicKey, '.pem')) {
            $publicKey = openssl_pkey_get_public(
                Str::startsWith($publicKey, 'file://') ? $publicKey : 'file://' . $publicKey
            );
        } else {
            $publicKey = "-----BEGIN PUBLIC KEY-----\n" .
                wordwrap($publicKey, 64, "\n", true) .
                "\n-----END PUBLIC KEY-----";
        }

        $sign = $sign ?? $data['sign'];

        $toVerify = $sync ? json_encode($data, JSON_UNESCAPED_UNICODE) : $this->getSignContent($data, true);

        $isVerify = 1 === openssl_verify($toVerify, base64_decode($sign), $publicKey, OPENSSL_ALGO_SHA256);

        if (is_resource($publicKey)) {
            openssl_free_key($publicKey);
        }

        return $isVerify;
    }

    /**
     * Get signContent that is to be signed.
     *
     * @param bool $verify
     * @author yansongda <me@yansongda.cn>
     *
     */
    public function getSignContent(array $data, $verify = false): string
    {
        ksort($data);

        $stringToBeSigned = '';
        foreach ($data as $k => $v) {
            if ($verify && 'sign' != $k && 'sign_type' != $k) {
                $stringToBeSigned .= $k . '=' . $v . '&';
            }
            if (!$verify && '' !== $v && !is_null($v) && 'sign' != $k && '@' != substr($v, 0, 1)) {
                $stringToBeSigned .= $k . '=' . $v . '&';
            }
        }

        $this->abstractPay->logger('Alipay Generate Sign Content Before Trim {0} {1}', [$data, $stringToBeSigned]);

        return trim($stringToBeSigned, '&');
    }


    /**
     * 生成应用证书SN.
     * @param $certPath
     * @return string
     * @throws PayException
     */
    public function getCertSN($certPath): string
    {
        if (!is_file($certPath)) {
            throw new PayException('unknown certPath -- [getCertSN]');
        }
        $x509data = file_get_contents($certPath);
        if (false === $x509data) {
            throw new PayException('Alipay CertSN Error -- [getCertSN]');
        }
        openssl_x509_read($x509data);
        $certdata = openssl_x509_parse($x509data);
        if (empty($certdata)) {
            throw new PayException('Alipay openssl_x509_parse Error -- [getCertSN]');
        }
        $issuer_arr = [];
        foreach ($certdata['issuer'] as $key => $val) {
            $issuer_arr[] = $key . '=' . $val;
        }
        $issuer = implode(',', array_reverse($issuer_arr));

        $this->abstractPay->logger('getCertSN: {0} {1} {2}', [$certPath, $issuer, $certdata['serialNumber']]);

        return md5($issuer . $certdata['serialNumber']);
    }

    /**
     * 生成支付宝根证书SN.
     * @param $certPath
     * @return string
     * @throws PayException
     */
    public function getRootCertSN($certPath)
    {
        if (!is_file($certPath)) {
            throw new PayException('unknown certPath -- [getRootCertSN]');
        }
        $x509data = file_get_contents($certPath);
        if (false === $x509data) {
            throw new PayException('Alipay CertSN Error -- [getRootCertSN]');
        }
        $kCertificateEnd = '-----END CERTIFICATE-----';
        $certStrList = explode($kCertificateEnd, $x509data);
        $md5_arr = [];
        foreach ($certStrList as $one) {
            if (!empty(trim($one))) {
                $_x509data = $one . $kCertificateEnd;
                openssl_x509_read($_x509data);
                $_certdata = openssl_x509_parse($_x509data);
                if (in_array($_certdata['signatureTypeSN'], ['RSA-SHA256', 'RSA-SHA1'])) {
                    $issuer_arr = [];
                    foreach ($_certdata['issuer'] as $key => $val) {
                        $issuer_arr[] = $key . '=' . $val;
                    }
                    $_issuer = implode(',', array_reverse($issuer_arr));
                    if (0 === strpos($_certdata['serialNumber'], '0x')) {
                        $serialNumber = self::bchexdec($_certdata['serialNumber']);
                    } else {
                        $serialNumber = $_certdata['serialNumber'];
                    }
                    $md5_arr[] = md5($_issuer . $serialNumber);
                    $this->abstractPay->logger('getRootCertSN Sub: {0} {1} {2}', [$certPath, $_issuer, $serialNumber]);
                }
            }
        }

        return implode('_', $md5_arr);
    }

    /**
     * processingApiResult.
     *
     * @param $data
     * @param $result
     * @param bool $response
     * @return array
     * @throws InvalidConfigException
     * @throws InvalidSignException
     * @throws PayException
     */
    public function processingApiResult($data, $result, $response = false)
    {
        if ($response) {
            return $result;
        }

        $method = str_replace('.', '_', $data['method']) . '_response';

        if (!isset($result['sign']) || '10000' != $result[$method]['code']) {
            throw new PayResponseException('Get Alipay API Error:' . $result[$method]['msg'] . (isset($result[$method]['sub_code']) ? (' - ' . $result[$method]['sub_code']) : ''),0,null, $result);
        }

        if ($this->verifySign($result[$method], true, $result['sign'])) {
            return $result[$method];
        }

        throw new InvalidSignException('Alipay Sign Verify FAILED', $result);
    }

    /**
     * 0x转高精度数字.
     * @param $hex
     * @return int|string
     */
    private static function bchexdec($hex)
    {
        $dec = 0;
        $len = strlen($hex);
        for ($i = 1; $i <= $len; ++$i) {
            if (ctype_xdigit($hex[$i - 1])) {
                $dec = bcadd($dec, bcmul(strval(hexdec($hex[$i - 1])), bcpow('16', strval($len - $i))));
            }
        }

        return str_replace('.00', '', $dec);
    }
}