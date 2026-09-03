<?php

namespace Crmeb\Easypay\Exception;

/**
 * 支付响应异常
 */
class PayResponseException extends \Exception
{

    protected $response;

    public function __construct($message = "", $code = 0, \Throwable $previous = null, $response = null)
    {
        parent::__construct($message, $code, $previous);
        $this->response = $response;
    }

    public function getResponse()
    {
        return $this->response;
    }
}