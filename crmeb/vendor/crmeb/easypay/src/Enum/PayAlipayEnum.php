<?php

namespace Crmeb\Easypay\Enum;

/**
 * 支付网关枚举
 * Class PayGatewayEnum
 * @package Crmeb\Enum
 */
class PayAlipayEnum
{
    // APP支付接口
    const APP_PAY_URL = 'alipay.trade.app.pay';

    // 扫码支付接口
    const NATIVE_PAY_URL = 'alipay.trade.precreate';

    // H5支付接口
    const WAP_PAY_URL = 'alipay.trade.wap.pay';

    // 公众号支付接口
    const JSAPI_PAY_URL = 'alipay.trade.create';

    // 单笔转账接口
    const TRANSFER_PAY_URL = 'alipay.fund.trans.uni.transfer';

    // 网页支付接口
    const PAGE_PAY_URL = 'alipay.trade.page.pay';

    // 商家扣款接口
    const POS_PAY_URL = 'alipay.trade.pay';

    // 退款接口
    const REFUND_QUERY_URL = 'alipay.trade.fastpay.refund.query';
    // 订单查询接口
    const ORDER_URL = 'alipay.trade.query';
    // 退款接口
    const REFUND_URL = 'alipay.trade.refund';

    // 网关映射
    const GATEWAY_MAP = [
        PayGatewayTypeEnum::APP_PAY      => self::APP_PAY_URL,
        PayGatewayTypeEnum::NATIVE_PAY   => self::NATIVE_PAY_URL,
        PayGatewayTypeEnum::WAP_PAY      => self::WAP_PAY_URL,
        PayGatewayTypeEnum::JSAPI_PAY    => self::JSAPI_PAY_URL,
        PayGatewayTypeEnum::TRANSFER_PAY => self::TRANSFER_PAY_URL,
        PayGatewayTypeEnum::PAGE_PAY     => self::PAGE_PAY_URL,
        PayGatewayTypeEnum::POS_PAY      => self::POS_PAY_URL,
    ];
}