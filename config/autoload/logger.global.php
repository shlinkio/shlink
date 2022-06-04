<?php

declare(strict_types=1);

namespace Shlinkio\Shlink;

use Monolog\Level;
use Monolog\Logger;
use PhpMiddleware\RequestId;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Common\Logger\LoggerFactory;
use Shlinkio\Shlink\Common\Logger\LoggerType;

$common = [
    'level' => Level::Info->value,
    'processors' => [RequestId\MonologProcessor::class],
    'line_format' => '[%datetime%] [%extra.request_id%] %channel%.%level_name% - %message%',
];

return [

    'logger' => [
        'Shlink' => [
            'type' => LoggerType::FILE->value,
            ...$common,
        ],
        'Access' => [
            'type' => LoggerType::STREAM->value,
            ...$common,
        ],
    ],

    'dependencies' => [
        'factories' => [
            'Logger_Shlink' => [LoggerFactory::class, 'Shlink'],
            'Logger_Access' => [LoggerFactory::class, 'Access'],
        ],
        'aliases' => [
            'logger' => 'Logger_Shlink',
            Logger::class => 'Logger_Shlink',
            LoggerInterface::class => 'Logger_Shlink',
        ],
    ],

    'mezzio-swoole' => [
        'swoole-http-server' => [
            'logger' => [
                'logger-name' => 'Logger_Access',
                'format' => '%u "%r" %>s %B',
            ],
        ],
    ],

];
