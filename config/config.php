<?php
declare(strict_types=1);

use Acelaya\ExpressiveErrorHandler;
use Shlinkio\Shlink\CLI;
use Shlinkio\Shlink\Common;
use Shlinkio\Shlink\Core;
use Shlinkio\Shlink\Rest;
use Zend\ConfigAggregator;
use Zend\Expressive;

/**
 * Configuration files are loaded in a specific order. First ``global.php``, then ``*.global.php``.
 * then ``local.php`` and finally ``*.local.php``. This way local settings overwrite global settings.
 *
 * The configuration can be cached. This can be done by setting ``config_cache_enabled`` to ``true``.
 *
 * Obviously, if you use closures in your config you can't cache it.
 */

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
    Rest\ConfigProvider::class,
    new ConfigAggregator\ZendConfigProvider('config/{autoload/{{,*.}global,{,*.}local},params/generated_config}.php'),
], 'data/cache/app_config.php'))->getMergedConfig();
