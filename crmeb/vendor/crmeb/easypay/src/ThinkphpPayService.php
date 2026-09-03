<?php

namespace Crmeb\Easypay;

use think\Service;

/**
 *
 */
class ThinkphpPayService extends Service
{
    public function register()
    {
        $this->app->bind(Facade::class, function () {
            return (new Facade())->registerCache($this->app->cache)->registerLogger($this->app->log);
        });
    }
}