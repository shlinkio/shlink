<?php

declare(strict_types=1);

namespace Shlinkio\Shlink;

use Laminas\ConfigAggregator;
use Laminas\Diactoros;
use Mezzio;
use Mezzio\ProblemDetails;
use Shlinkio\Shlink\Config\ConfigAggregator\EnvVarLoaderProvider;

use function Shlinkio\Shlink\Config\env;
use function Shlinkio\Shlink\Core\enumValues;

$isTestEnv = env('APP_ENV') === 'test';

return (new ConfigAggregator\ConfigAggregator(
    providers: [
        ! $isTestEnv
            ? new EnvVarLoaderProvider('config/params/generated_config.php', enumValues(Core\Config\EnvVars::class))
            : new ConfigAggregator\ArrayProvider([]),
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
        // Local config should not be loaded during tests, whereas test config should be loaded ONLY during tests
        new ConfigAggregator\PhpFileProvider(
            $isTestEnv ? 'config/test/*.global.php' : 'config/autoload/{,*.}local.php',
        ),
        // Routes have to be loaded last
        new ConfigAggregator\PhpFileProvider('config/autoload/routes.config.php'),
    ],
    cachedConfigFile: 'data/cache/app_config.php',
    postProcessors: [
        Core\Config\PostProcessor\BasePathPrefixer::class,
        Core\Config\PostProcessor\MultiSegmentSlugProcessor::class,
        Core\Config\PostProcessor\ShortUrlMethodsProcessor::class,
    ],
))->getMergedConfig();
