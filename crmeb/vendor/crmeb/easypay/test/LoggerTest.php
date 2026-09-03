<?php

use Crmeb\Easypay\Log\FileLogger;
use PHPUnit\Framework\TestCase;


class LoggerTest extends TestCase
{

    public function testLog()
    {
        $logger = new FileLogger(dirname(__DIR__) . '/logs');

        $logger->info('哈哈');
    }
}