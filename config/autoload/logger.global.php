<?php

declare(strict_types=1);

namespace Shlinkio\Shlink;

use Monolog\Formatter;
use Monolog\Handler;
use Monolog\Logger;
use Monolog\Processor;
use MonologFactory\DiContainerLoggerFactory;
use Psr\Log\LoggerInterface;

use const PHP_EOL;

$processors = [
    'exception_with_new_line' => [
        'name' => Common\Logger\Processor\ExceptionWithNewLineProcessor::class,
    ],
    'psr3' => [
        'name' => Processor\PsrLogMessageProcessor::class,
    ],
];
$formatter = [
    'name' => Formatter\LineFormatter::class,
    'params' => [
        'format' => '[%datetime%] %channel%.%level_name% - %message%' . PHP_EOL,
        'include_stacktraces' => true,
    ],
];

return [

    'logger' => [
        'Shlink' => [
            'name' => 'Shlink',
            'handlers' => [
                'shlink_handler' => [
                    'name' => Handler\RotatingFileHandler::class,
                    'params' => [
                        'level' => Logger::INFO,
                        'filename' => 'data/log/shlink_log.log',
                        'max_files' => 30,
                    ],
                    'formatter' => $formatter,
                ],
            ],
            'processors' => $processors,
        ],
        'Access' => [
            'name' => 'Access',
            'handlers' => [
                'access_handler' => [
                    'name' => Handler\StreamHandler::class,
                    'params' => [
                        'level' => Logger::INFO,
                        'stream' => 'php://stdout',
                    ],
                    'formatter' => $formatter,
                ],
            ],
            'processors' => $processors,
        ],
    ],

    'dependencies' => [
        'factories' => [
            'Logger_Shlink' => [DiContainerLoggerFactory::class, 'Shlink'],
            'Logger_Access' => [DiContainerLoggerFactory::class, 'Access'],
        ],
        'aliases' => [
            'logger' => 'Logger_Shlink',
            Logger::class => 'Logger_Shlink',
            LoggerInterface::class => 'Logger_Shlink',
        ],
    ],

    'zend-expressive-swoole' => [
        'swoole-http-server' => [
            'logger' => [
                'logger-name' => 'Logger_Access',
            ],
        ],
    ],

];
