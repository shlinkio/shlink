<?php

declare(strict_types=1);

namespace Shlinkio\Shlink;

use Laminas\ConfigAggregator;
use Laminas\Diactoros;
use Mezzio;
use Mezzio\ProblemDetails;
use Mezzio\Swoole;
use Shlinkio\Shlink\Config\ConfigAggregator\EnvVarLoaderProvider;

use function class_exists;
use function Shlinkio\Shlink\Config\env;
use function Shlinkio\Shlink\Config\openswooleIsInstalled;
use function Shlinkio\Shlink\Config\runningInRoadRunner;
use function Shlinkio\Shlink\Core\enumValues;

use const PHP_SAPI;

$isTestEnv = env('APP_ENV') === 'test';
$enableSwoole = PHP_SAPI === 'cli' && openswooleIsInstalled() && ! runningInRoadRunner();

return (new ConfigAggregator\ConfigAggregator([
    ! $isTestEnv
        ? new EnvVarLoaderProvider('config/params/generated_config.php', enumValues(Core\Config\EnvVars::class))
        : new ConfigAggregator\ArrayProvider([]),
    Mezzio\ConfigProvider::class,
    Mezzio\Router\ConfigProvider::class,
    Mezzio\Router\FastRouteRouter\ConfigProvider::class,
    $enableSwoole && class_exists(Swoole\ConfigProvider::class)
        ? Swoole\ConfigProvider::class
        : new ConfigAggregator\ArrayProvider([]),
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
    $isTestEnv
        ? new ConfigAggregator\PhpFileProvider('config/test/*.global.php')
        : new ConfigAggregator\ArrayProvider([]),
    // Routes have to be loaded last
    new ConfigAggregator\PhpFileProvider('config/autoload/routes.config.php'),
], 'data/cache/app_config.php', [
    Core\Config\PostProcessor\BasePathPrefixer::class,
    Core\Config\PostProcessor\MultiSegmentSlugProcessor::class,
    Core\Config\PostProcessor\ShortUrlMethodsProcessor::class,
]))->getMergedConfig();
