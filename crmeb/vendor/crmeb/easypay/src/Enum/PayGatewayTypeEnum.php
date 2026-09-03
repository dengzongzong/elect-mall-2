<?php

namespace Crmeb\Easypay\Enum;

class PayGatewayTypeEnum
{
    // APP支付
    const APP_PAY = 'app';

    // 扫码支付
    const NATIVE_PAY = 'scan';

    // H5支付
    const WAP_PAY = 'wap';

    // 公众号支付
    const JSAPI_PAY = 'jsapi';

    // 小程序支付
    const MINI_PAY = 'mini';

    // 单笔转账接口
    const TRANSFER_PAY = 'transfer';

    // 商家扣款
    const POS_PAY = 'pos';

    // 网页支付
    const PAGE_PAY = 'page';
}