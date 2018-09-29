<?php
declare(strict_types=1);

namespace Shlinkio\Shlink;

use Acelaya\ExpressiveErrorHandler;
use Zend\ConfigAggregator;
use Zend\Expressive;

return (new ConfigAggregator\ConfigAggregator([
    Expressive\ConfigProvider::class,
    Expressive\Router\ConfigProvider::class,
    Expressive\Router\FastRouteRouter\ConfigProvider::class,
    Expressive\Plates\ConfigProvider::class,
    Expressive\Helper\ConfigProvider::class,
    \class_exists(Expressive\Swoole\ConfigProvider::class)
        ? Expressive\Swoole\ConfigProvider::class
        : new ConfigAggregator\ArrayProvider([]),
    ExpressiveErrorHandler\ConfigProvider::class,
    Common\ConfigProvider::class,
    Core\ConfigProvider::class,
    CLI\ConfigProvider::class,
    Installer\ConfigProvider::class,
    Rest\ConfigProvider::class,
    new ConfigAggregator\ZendConfigProvider('config/{autoload/{{,*.}global,{,*.}local},params/generated_config}.php'),
], 'data/cache/app_config.php'))->getMergedConfig();
