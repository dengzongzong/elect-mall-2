<?php

use PHPUnit\Framework\TestCase;
use Crmeb\Easypay\Exception\PayResponseException;

class UnionPayTest extends TestCase
{

    public function testRequest()
    {
       $host= 'https://openapi.unionpay.com/';

       var_dump(parse_url($host,1));
    }
}