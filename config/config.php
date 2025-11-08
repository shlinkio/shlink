<?php

declare(strict_types=1);

namespace Shlinkio\Shlink;

use Laminas\ConfigAggregator;
use Laminas\Diactoros;
use Mezzio;
use Mezzio\ProblemDetails;
use Shlinkio\Shlink\Core\Config\EnvVars;

return new ConfigAggregator\ConfigAggregator(
    providers: [
        Mezzio\ConfigProvider::class,
        Mezzio\Router\ConfigProvider::class,
        Mezzio\Router\FastRouteRouter\ConfigProvider::class,
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
        new ConfigAggregator\PhpFileProvider('config/autoload/{,*.}global.php'),
        // Test config should be loaded ONLY during tests
        EnvVars::isTestEnv()
            ? new ConfigAggregator\PhpFileProvider('config/test/*.global.php')
            : new ConfigAggregator\ArrayProvider([]),
        // Routes have to be loaded last
        new ConfigAggregator\PhpFileProvider('config/autoload/routes.config.php'),
    ],
    cachedConfigFile: 'data/cache/app_config.php',
    postProcessors: [
        Core\Config\PostProcessor\BasePathPrefixer::class,
        Core\Config\PostProcessor\MultiSegmentSlugProcessor::class,
        Core\Config\PostProcessor\ShortUrlMethodsProcessor::class,
    ],
)->getMergedConfig();
