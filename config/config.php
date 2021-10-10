<?php

declare(strict_types=1);

namespace Shlinkio\Shlink;

use Laminas\ConfigAggregator;
use Laminas\Diactoros;
use Mezzio;
use Mezzio\ProblemDetails;
use Mezzio\Swoole;

use function class_exists;
use function Shlinkio\Shlink\Common\env;

return (new ConfigAggregator\ConfigAggregator([
    Mezzio\ConfigProvider::class,
    Mezzio\Router\ConfigProvider::class,
    Mezzio\Router\FastRouteRouter\ConfigProvider::class,
    class_exists(Swoole\ConfigProvider::class) ? Swoole\ConfigProvider::class : new ConfigAggregator\ArrayProvider([]),
    ProblemDetails\ConfigProvider::class,
    Diactoros\ConfigProvider::class,
    Common\ConfigProvider::class,
    Config\ConfigProvider::class,
    Importer\ConfigProvider::class,
    IpGeolocation\ConfigProvider::class,
    EventDispatcher\ConfigProvider::class,
    Core\ConfigProvider::class,
    CLI\ConfigProvider::class,
    Rest\ConfigProvider::class,
    new ConfigAggregator\PhpFileProvider('config/autoload/{{,*.}global,{,*.}local}.php'),
    env('APP_ENV') === 'test'
        ? new ConfigAggregator\PhpFileProvider('config/test/*.global.php')
        // Deprecated. When the SimplifiedConfigParser is removed, load only generated_config.php here
        : new ConfigAggregator\LaminasConfigProvider('config/params/{generated_config.php,*.config.{php,json}}'),
], 'data/cache/app_config.php', [
    Core\Config\SimplifiedConfigParser::class,
    Core\Config\BasePathPrefixer::class,
    Core\Config\DeprecatedConfigParser::class,
]))->getMergedConfig();
