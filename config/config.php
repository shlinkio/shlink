<?php
declare(strict_types=1);

namespace Shlinkio\Shlink;

use Acelaya\ExpressiveErrorHandler;
use Zend\ConfigAggregator;
use Zend\Expressive;
use function Shlinkio\Shlink\Common\env;

return (new ConfigAggregator\ConfigAggregator([
    Expressive\ConfigProvider::class,
    Expressive\Router\ConfigProvider::class,
    Expressive\Router\FastRouteRouter\ConfigProvider::class,
    Expressive\Plates\ConfigProvider::class,
    Expressive\Swoole\ConfigProvider::class,
    ExpressiveErrorHandler\ConfigProvider::class,
    Common\ConfigProvider::class,
    Core\ConfigProvider::class,
    CLI\ConfigProvider::class,
    Rest\ConfigProvider::class,
    new ConfigAggregator\PhpFileProvider('config/autoload/{{,*.}global,{,*.}local}.php'),
    new ConfigAggregator\ZendConfigProvider('config/params/{generated_config.php,*.config.{php,json}}'),
    env('APP_ENV') === 'test'
        ? new ConfigAggregator\PhpFileProvider('config/test/*.global.php')
        : new ConfigAggregator\ArrayProvider([]),
], 'data/cache/app_config.php'))->getMergedConfig();
