<?php

namespace Crmeb\Easypay\Interfaces;

interface PayInterface
{
    /**
     * Pay an order.
     * @param $gateway
     * @param $params
     * @return mixed
     */
    public function pay($gateway, array $params = []);

    /**
     * Query an order.
     * @param string|array $order
     * @return array
     */
    public function find($order, string $type);

    /**
     * Refund an order.
     * @param array $order
     * @param string $type
     * @return array
     */
    public function refund(array $order, string $type);

    /**
     * Cancel an order.
     * @param string|array $order
     * @param string $type
     * @return array
     */
    public function cancel($order, string $type);

    /**
     * Close an order.
     * @param string $orderId
     * @param string $type
     * @param string $srcReserve 附加数据，原样返回
     * @return array
     */
    public function close(string $orderId, string $type, string $srcReserve = '');

    /**
     * Verify a request.
     * @param array $content
     * @return array
     */
    public function verify(array $content);
}