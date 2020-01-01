<?php

declare(strict_types=1);

namespace Shlinkio\Shlink;

use Laminas\ConfigAggregator;
use Laminas\ZendFrameworkBridge;
use Mezzio;
use Mezzio\ProblemDetails;

use function Shlinkio\Shlink\Common\env;

return (new ConfigAggregator\ConfigAggregator([
    Mezzio\ConfigProvider::class,
    Mezzio\Router\ConfigProvider::class,
    Mezzio\Router\FastRouteRouter\ConfigProvider::class,
    Mezzio\Plates\ConfigProvider::class,
    Mezzio\Swoole\ConfigProvider::class,
    ProblemDetails\ConfigProvider::class,
    Common\ConfigProvider::class,
    IpGeolocation\ConfigProvider::class,
    Core\ConfigProvider::class,
    CLI\ConfigProvider::class,
    Rest\ConfigProvider::class,
    EventDispatcher\ConfigProvider::class,
    new ConfigAggregator\PhpFileProvider('config/autoload/{{,*.}global,{,*.}local}.php'),
    env('APP_ENV') === 'test'
        ? new ConfigAggregator\PhpFileProvider('config/test/*.global.php')
        : new ConfigAggregator\LaminasConfigProvider('config/params/{generated_config.php,*.config.{php,json}}'),
], 'data/cache/app_config.php', [
    ZendFrameworkBridge\ConfigPostProcessor::class,
    Core\Config\SimplifiedConfigParser::class,
    Core\Config\BasePathPrefixer::class,
    Core\Config\DeprecatedConfigParser::class,
]))->getMergedConfig();
