<?php
declare(strict_types=1);

namespace Shlinkio\Shlink;

use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor;
use const PHP_EOL;

return [

    'logger' => [
        'formatters' => [
            'dashed' => [
                'format' => '[%datetime%] %channel%.%level_name% - %message%' . PHP_EOL,
                'include_stacktraces' => true,
            ],
        ],

        'handlers' => [
            'shlink_log_handler' => Common\Exec\ExecutionContext::currentContextIsSwoole() ? [
                'class' => StreamHandler::class,
                'level' => Logger::INFO,
                'stream' => 'php://stdout',
                'formatter' => 'dashed',
            ] : [
                'class' => RotatingFileHandler::class,
                'level' => Logger::INFO,
                'filename' => 'data/log/shlink_log.log',
                'formatter' => 'dashed',
                'max_files' => 30,
            ],
        ],

        'processors' => [
            'exception_with_new_line' => [
                'class' => Common\Logger\Processor\ExceptionWithNewLineProcessor::class,
            ],
            'psr3' => [
                'class' => Processor\PsrLogMessageProcessor::class,
            ],
        ],

        'loggers' => [
            'Shlink' => [
                'handlers' => ['shlink_log_handler'],
                'processors' => ['exception_with_new_line', 'psr3'],
            ],
        ],
    ],

];
