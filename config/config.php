<?php

declare(strict_types=1);

namespace Shlinkio\Shlink;

use Laminas\ConfigAggregator;
use Laminas\Diactoros;
use Mezzio;
use Mezzio\ProblemDetails;
use Mezzio\Swoole;

use function class_exists;
use function Shlinkio\Shlink\Config\env;
use function Shlinkio\Shlink\Core\putNotYetDefinedEnv;

use const PHP_SAPI;

$isCli = PHP_SAPI === 'cli';
$isTestEnv = env('APP_ENV') === 'test';

return (new ConfigAggregator\ConfigAggregator([
    ! $isTestEnv
        ? new ConfigAggregator\ArrayProvider((new ConfigAggregator\ConfigAggregator([
            new ConfigAggregator\PhpFileProvider('config/params/generated_config.php'),
        ], null, [function (array $generatedConfig) {
            foreach ($generatedConfig as $envVar => $value) {
                putNotYetDefinedEnv($envVar, $value);
            }

            return [];
        }]))->getMergedConfig())
        : new ConfigAggregator\ArrayProvider([]),
    Mezzio\ConfigProvider::class,
    Mezzio\Router\ConfigProvider::class,
    Mezzio\Router\FastRouteRouter\ConfigProvider::class,
    $isCli && class_exists(Swoole\ConfigProvider::class)
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
], 'data/cache/app_config.php', [
    Core\Config\BasePathPrefixer::class,
]))->getMergedConfig();
