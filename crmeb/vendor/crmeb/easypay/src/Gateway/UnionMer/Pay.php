<?php

namespace Crmeb\Easypay\Gateway\UnionMer;


use Crmeb\Easypay\Enum\PayGatewayTypeEnum;
use Crmeb\Easypay\Exception\PayException;
use Crmeb\Easypay\Config\UnionMerConfig;
use Crmeb\Easypay\Enum\PayUnionMerEnum;
use Crmeb\Easypay\Gateway\AbstractPay;
use Crmeb\Easypay\Interfaces\PayInterface;

/**
 * 银联商务支付
 */
class Pay extends AbstractPay implements PayInterface
{

    /**
     *  支持
     * @var Support
     */
    protected $support;

    /**
     *  支付参数
     * @var array
     */
    private $payload = [];

    /**
     * 初始化
     * @return void
     */
    public function init()
    {
        $this->support = new Support($this);

        $this->baseUri = $this->config->getBaseUri();

        /** @var UnionMerConfig $config */
        $config = $this->config;
        $this->payload = [
            'mid'              => $config->getMchId(),
            'tid'              => $config->getTid(),
            'notifyUrl'        => $config->getNotifyUrl(),
            'returnUrl'        => $config->getReturnUrl(),
            'requestTimestamp' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * 发起支付
     * 根据传入的支付网关和参数，选择对应的支付渠道（微信/支付宝）并调用相应接口
     *
     * @param string $gateway 支付网关类型，如 native、wap、jsapi 等
     * @param array  $params  支付请求参数，可包含 union_type、notify_url、return_url 等
     * @return array|string 返回支付结果，具体格式由 Support 层决定
     * @throws PayException 当网关或 union_type 不支持时抛出
     * @throws \Crmeb\Easypay\Exception\PayResponseException 当支付响应异常时抛出
     * @throws \GuzzleHttp\Exception\GuzzleException 当 HTTP 请求异常时抛出
     * @throws \Psr\SimpleCache\InvalidArgumentException 当缓存操作异常时抛出
     */
    public function pay($gateway, array $params = [])
    {
        // 校验支付网关是否支持
        if (!in_array($gateway, array_keys(PayUnionMerEnum::GATEWAY_MAP))) {
            throw  new PayException('不支持的支付渠道接口');
        }

        // 获取并移除 union_type，默认微信
        $unionType = $params['union_type'] ?? PayUnionMerEnum::UNION_TYPE_WECHAT;
        unset($params['union_type']);

        // 校验 union_type 是否合法
        if (!in_array($unionType, [PayUnionMerEnum::UNION_TYPE_WECHAT, PayUnionMerEnum::UNION_TYPE_ALIPAY])) {
            throw  new PayException('不支持的支付端口接口!');
        }

        // 根据网关和 union_type 获取请求地址
        $url = PayUnionMerEnum::GATEWAY_MAP[$gateway][$unionType] ?? null;
        if (!$url) {
            throw  new PayException(sprintf('不支持的支付接口:gateway %s unionType %s', $gateway, $unionType));
        }

        // 动态覆盖 notifyUrl / returnUrl
        $this->payload['notifyUrl'] = $params['notify_url'] ?? $this->payload['notifyUrl'];
        $this->payload['returnUrl'] = $params['return_url'] ?? $this->payload['returnUrl'];
        unset($params['return_url'], $params['notify_url']);

        // H5 支付 和 公众号支付：使用 query 方式发送请求
        if (in_array($gateway, [PayGatewayTypeEnum::WAP_PAY, PayGatewayTypeEnum::JSAPI_PAY])) {
            return $this->support->querySendRequest($url, array_merge($this->payload, $params));
        }

        // 其他支付：将参数合并到 payload 并使用 JSON 方式发送请求
        foreach ($params as $key => $value) {
            if ($value && is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }
            $this->payload[$key] = $value;
        }
        return $this->support->jsonSendRequest($url, $this->payload);
    }

    public function find($order, string $type)
    {
        // TODO: Implement find() method.
    }

    /**
     * 退款
     * @param array $order 退款订单数据
     * @param string $type 支付类型
     * @return mixed
     */
    public function refund(array $order, string $type)
    {
        // 复制基础参数并移除不需要的字段
        $params = $this->payload;
        unset($params['return_url'], $params['notify_url']);
        
        // 设置机构商户号
        $params['instMid'] = PayUnionMerEnum::INSTMID[$type] ?? '';
        // 设置退款金额
        $params['refundAmount'] = $order['refundAmount'] ?? 0;

        // 根据支付类型设置订单号和请求地址
        if ($type == 'qrcode') {
            // 退款请求地址
            $url = PayUnionMerEnum::NATIVE_PAY_REFUND_URL;
            // 二维码支付退款需要 billNo 和 billDate
            $params['billNo'] = $order['refundOrderId'] ?? '';
            $params['billDate'] = $order['billDate'] ?? '';
        } else {
            // 退款请求地址
            $url = PayUnionMerEnum::REFUND_ORDER_URL;
            // 非二维码支付退款只需要 merOrderId
            $params['merOrderId'] = $order['refundOrderId'] ?? '';
        }

        // 可选参数处理
        if (isset($order['msgId'])) {
            $params['msgId'] = $order['msgId'];
        }
        if (isset($order['srcReserve'])) {
            $params['srcReserve'] = $order['srcReserve'];
        }
        if (isset($order['refundOrderId'])) {
            $params['refundOrderId'] = $order['refundOrderId'];
        }
        if (isset($order['platformAmount'])) {
            $params['platformAmount'] = $order['platformAmount'];
        }
        if (isset($order['subOrders'])) {
            $params['subOrders'] = json_encode($order['subOrders'], JSON_UNESCAPED_UNICODE);
        }
        // 发送退款请求
        return $this->support->jsonSendRequest($url, $params);
    }

    public function cancel($order, string $type)
    {
        // TODO: Implement cancel() method.
    }

    /**
     * 关闭订单
     * @param string $orderId 商户订单号
     * @param string $type 支付类型（决定 instMid）
     * @param string $srcReserve 可选的源保留字段
     * @return mixed 网关返回结果
     */
    public function close(string $orderId, string $type, string $srcReserve = '')
    {
        // 复制基础参数并移除不需要的字段
        $params = $this->payload;
        unset($params['return_url'], $params['notify_url']);
        
        // 设置要关闭的订单号
        $params['merOrderId'] = $orderId;
        
        // 根据支付类型设置机构商户号
        $params['instMid'] = PayUnionMerEnum::INSTMID[$type] ?? '';
        
        // 如果提供了源保留字段，则加入参数
        if ($srcReserve) {
            $params['srcReserve'] = $srcReserve;
        }
        
        // 发送关闭订单请求
        return $this->support->jsonSendRequest(PayUnionMerEnum::CLOSE_ORDER_URL, $params);
    }

    public function verify($content)
    {
        // TODO: Implement verify() method.
    }
}
