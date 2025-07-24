<?php

declare(strict_types=1);

namespace Shlinkio\Shlink;

use Laminas\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Shlinkio\Shlink\Common\Logger\LoggerFactory;
use Shlinkio\Shlink\Common\Logger\LoggerType;
use Shlinkio\Shlink\Common\Middleware\AccessLogMiddleware;
use Shlinkio\Shlink\Common\Middleware\RequestIdMiddleware;
use Shlinkio\Shlink\Core\Config\EnvVars;
use Shlinkio\Shlink\Core\EventDispatcher\Helper\RequestIdProvider;
use Shlinkio\Shlink\EventDispatcher\Util\RequestIdProviderInterface;

use function Shlinkio\Shlink\Config\env;
use function Shlinkio\Shlink\Config\runningInRoadRunner;

return (static function (): array {
    $isDev = EnvVars::isDevEnv();
    $format = EnvVars::LOGS_FORMAT->loadFromEnv();
    $buildCommonConfig = static fn (bool $addNewLine = false) => [
        'level' => $isDev ? Level::Debug->value : Level::Info->value,
        'processors' => [RequestIdMiddleware::class],
        'formatter' => [
            'type' => $format,
            'add_new_line' => $addNewLine,
            'line_format' =>
                '[%datetime%] [%extra.' . RequestIdMiddleware::ATTRIBUTE . '%] %channel%.%level_name% - %message%',
        ],
    ];

    // In dev env or the docker container, stream Shlink logs to stderr, otherwise send them to a file
    $useStreamForShlinkLogger = $isDev || env('SHLINK_RUNTIME') !== null;

    return [

        'logger' => [
            'Shlink' => $useStreamForShlinkLogger ? [
                'type' => LoggerType::STREAM->value,
                'destination' => 'php://stderr',
                ...$buildCommonConfig(),
            ] : [
                'type' => LoggerType::FILE->value,
                ...$buildCommonConfig(),
            ],
            'Access' => [
                'type' => LoggerType::STREAM->value,
                'destination' => 'php://stderr',
                ...$buildCommonConfig(! runningInRoadRunner()),
            ],
        ],

        'dependencies' => [
            'factories' => [
                'Logger_Shlink' => [LoggerFactory::class, 'Shlink'],
                'Logger_Access' => [LoggerFactory::class, 'Access'],
                NullLogger::class => InvokableFactory::class,
                RequestIdProvider::class => ConfigAbstractFactory::class,
            ],
            'aliases' => [
                'logger' => 'Logger_Shlink',
                Logger::class => 'Logger_Shlink',
                LoggerInterface::class => 'Logger_Shlink',
                AccessLogMiddleware::LOGGER_SERVICE_NAME => 'Logger_Access',
                RequestIdProviderInterface::class => RequestIdProvider::class,
            ],
        ],

        ConfigAbstractFactory::class => [
            RequestIdProvider::class => [RequestIdMiddleware::class],
        ],

    ];
})();
