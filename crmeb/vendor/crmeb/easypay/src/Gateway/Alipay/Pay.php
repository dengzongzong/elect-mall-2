<?php

namespace Crmeb\Easypay\Gateway\Alipay;

use Crmeb\Easypay\Config\AlipayConfig;
use Crmeb\Easypay\Exception\InvalidConfigException;
use Crmeb\Easypay\Exception\InvalidSignException;
use Crmeb\Easypay\Exception\PayException;
use Crmeb\Easypay\Enum\PayAlipayEnum;
use Crmeb\Easypay\Enum\PayGatewayTypeEnum;
use Crmeb\Easypay\Gateway\AbstractPay;
use Crmeb\Easypay\Interfaces\PayInterface;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;

/**
 *  支付宝
 */
class Pay extends AbstractPay implements PayInterface
{
    /**
     * @var Support
     */
    private $support;

    /**
     *  支付参数
     * @var array
     */
    private $payload = [];

    /**
     * 初始化
     * @throws PayException
     */
    public function init()
    {
        $this->support = new Support($this);
        /** @var AlipayConfig $config */
        $config = $this->config;

        $this->payload = [
            'app_id'      => $config->getAppid(),
            'method'      => '',
            'format'      => 'JSON',
            'charset'     => 'utf-8',
            'sign_type'   => 'RSA2',
            'version'     => '1.0',
            'return_url'  => $config->getReturnUrl(),
            'notify_url'  => $config->getNotifyUrl(),
            'timestamp'   => date('Y-m-d H:i:s'),
            'sign'        => '',
            'biz_content' => '',
        ];

        if ($config->getPublicKey() && $config->getRootCertPath()) {
            $this->payload['app_cert_sn'] = $this->support->getCertSN($config->getPublicKey());
            $this->payload['alipay_root_cert_sn'] = $this->support->getRootCertSN($config->getRootCertPath());
        }

        $this->baseUri = $config->getBaseUri();
    }

    /**
     *  支付
     * @param string $gateway
     * @param array $params
     * @return mixed
     * @throws PayException
     */
    public function pay(string $gateway = PayGatewayTypeEnum::JSAPI_PAY, array $params = [])
    {
        if (!in_array($gateway, array_keys(PayAlipayEnum::GATEWAY_MAP))) {
            throw  new PayException('不支持的支付接口');
        }
        $this->payload['method'] = PayAlipayEnum::GATEWAY_MAP[$gateway];

        $this->payload['return_url'] = $params['return_url'] ?? $this->payload['return_url'];
        $this->payload['notify_url'] = $params['notify_url'] ?? $this->payload['notify_url'];

        unset($params['return_url'], $params['notify_url']);

        $this->payload['biz_content'] = $params;

        $payload = array_filter($this->payload, function ($value) {
            return '' !== $value && !is_null($value);
        });

        $gatewayMethod = $gateway . 'CreatePay';

        if (method_exists($this, $gatewayMethod)) {
            return $this->$gatewayMethod($payload);
        }

        throw  new PayException('不支持的支付接口');
    }

    /**
     *  发送请求
     * @param array $payload
     * @return array
     * @throws GuzzleException
     * @throws PayException
     * @throws InvalidConfigException
     * @throws InvalidSignException
     */
    protected function send(array $payload)
    {
        $payload['sign'] = $this->support->generateSign($payload);
        if (!is_array($payload)) {
            $response = $this->bodySendRequest('', 'post', $payload);
        } else {
            $response = $this->formSendRequest('', 'post', $payload);
        }
        $result = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw  new PayException('json_decode error:' . json_last_error_msg());
        }

        return $this->support->processingApiResult($payload, $result);
    }

    /**
     *  jsapi创建支付
     * @param array $payload
     * @return mixed
     * @throws GuzzleException
     * @throws InvalidConfigException
     * @throws InvalidSignException
     * @throws PayException
     */
    protected function jsapiCreatePay(array $payload)
    {
        $biz_array = $payload['biz_content'];
        if (empty($biz_array['buyer_id'])) {
            throw new InvalidArgumentException('buyer_id required');
        }
        $payload['biz_content'] = json_encode($biz_array);

        return $this->send($payload);
    }

    /**
     *  app创建支付
     * @param array $payload
     * @return array
     */
    protected function appCreatePay(array $payload)
    {
        $payload['biz_content'] = json_encode($payload['biz_content']);
        $payload['sign'] = $this->support->generateSign($payload);

        return $payload;
    }

    /**
     *  wap创建支付
     * @param array $payload
     * @return string
     * @throws InvalidConfigException
     */
    protected function wapCreatePay(array $payload)
    {
        $payload['biz_content']['product_code'] = 'QUICK_WAP_WAY';
        $payload['biz_content'] = json_encode($payload['biz_content']);

        $payload['sign'] = $this->support->generateSign($payload);

        return $this->buildPayHtml($this->baseUri, $payload);
    }

    /**
     *   网页创建支付
     * @param array $payload
     * @return string
     * @throws InvalidConfigException
     */
    protected function pageCreatePay(array $payload)
    {
        $payload['biz_content']['product_code'] = 'FAST_INSTANT_TRADE_PAY';
        $payload['biz_content'] = json_encode($payload['biz_content']);

        $payload['sign'] = $this->support->generateSign($payload);

        return $this->buildPayHtml($this->baseUri, $payload);
    }

    /**
     *   pos创建支付
     * @param array $payload
     * @return array
     * @throws GuzzleException
     * @throws PayException
     * @throws InvalidConfigException
     * @throws InvalidSignException
     */
    protected function posCreatePay(array $payload)
    {
        $payload['biz_content'] = json_encode(array_merge(
            $payload['biz_content'],
            [
                'product_code' => 'FACE_TO_FACE_PAYMENT',
                'scene'        => 'bar_code',
            ]
        ));

        return $this->send($payload);
    }

    /**
     *  扫码创建支付
     * @param array $payload
     * @return array
     * @throws GuzzleException
     * @throws InvalidConfigException
     * @throws InvalidSignException
     * @throws PayException
     */
    protected function scanCreatePay(array $payload)
    {
        return $this->jsapiCreatePay($payload);
    }

    /**
     *  转账创建支付
     * @param array $payload
     * @return array
     * @throws GuzzleException
     * @throws PayException
     * @throws InvalidConfigException
     * @throws InvalidSignException
     */
    protected function transferCreatePay(array $payload)
    {
        return $this->send($payload);
    }

    /**
     *  构建支付html
     * @param string $endpoint
     * @param array $payload
     * @param string $method
     * @return string
     */
    protected function buildPayHtml(string $endpoint, array $payload, string $method = 'POST')
    {
        $sHtml = "<form id='alipay_submit' name='alipay_submit' action='" . $endpoint . "' method='" . $method . "'>";
        foreach ($payload as $key => $val) {
            $val = str_replace("'", '&apos;', $val);
            $sHtml .= "<input type='hidden' name='" . $key . "' value='" . $val . "'/>";
        }
        $sHtml .= "<input type='submit' value='ok' style='display:none;'></form>";
        $sHtml .= "<script>document.forms['alipay_submit'].submit();</script>";

        return $sHtml;
    }

    /**
     *  查询
     * @param $order
     * @param string $type
     * @return array
     * @throws GuzzleException
     * @throws InvalidConfigException
     * @throws InvalidSignException
     * @throws PayException
     */
    public function find($order, string $type)
    {
        if (!in_array($type, ['refund', 'wap'])) {
            throw  new InvalidArgumentException('invalid type refund or wap');
        }

        if ('refund' === $type) {
            $this->payload['method'] = PayAlipayEnum::REFUND_QUERY_URL;
        } else {
            $this->payload['method'] = PayAlipayEnum::ORDER_URL;
        }

        $this->payload['biz_content'] = json_encode(is_array($order) ? $order : ['out_trade_no' => $order]);

        return $this->send($this->payload);
    }

    /**
     *  退款
     * @param array $order
     * @return array
     * @throws GuzzleException
     * @throws InvalidConfigException
     * @throws InvalidSignException
     * @throws PayException
     */
    public function refund(array $order)
    {
        $this->payload['method'] = PayAlipayEnum::REFUND_URL;
        $this->payload['biz_content'] = json_encode($order);

        return $this->send($this->payload);
    }

    /**
     *  取消
     * @param $order
     * @return array
     * @throws GuzzleException
     * @throws InvalidConfigException
     * @throws InvalidSignException
     * @throws PayException
     */
    public function cancel($order)
    {
        $this->payload['method'] = 'alipay.trade.cancel';
        $this->payload['biz_content'] = json_encode(is_array($order) ? $order : ['out_trade_no' => $order]);

        return $this->send($this->payload);
    }

    /**
     * 关闭
     * @param $order
     * @return array
     * @throws GuzzleException
     * @throws InvalidConfigException
     * @throws InvalidSignException
     * @throws PayException
     */
    public function close($order)
    {
        $this->payload['method'] = 'alipay.trade.close';
        $this->payload['biz_content'] = json_encode(is_array($order) ? $order : ['out_trade_no' => $order]);
        return $this->send($this->payload);
    }

    /**
     * 验证回调数据
     * @param array $content
     * @return array
     * @throws InvalidConfigException
     * @throws InvalidSignException
     */
    public function verify(array $content)
    {
        if (isset($content['fund_bill_list'])) {
            $content['fund_bill_list'] = htmlspecialchars_decode($content['fund_bill_list']);
        }

        if ($this->support->verifySign($content)) {
            return $content;
        }

        throw new InvalidSignException('Alipay Sign Verify FAILED', $content);
    }
}