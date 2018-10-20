<?php
declare(strict_types=1);

namespace Shlinkio\Shlink;

use Monolog\Handler\RotatingFileHandler;
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
            'rotating_file_handler' => [
                'class' => RotatingFileHandler::class,
                'level' => Logger::INFO,
                'filename' => 'data/log/shlink_log.log',
                'max_files' => 30,
                'formatter' => 'dashed',
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
                'handlers' => ['rotating_file_handler'],
                'processors' => ['exception_with_new_line', 'psr3'],
            ],
        ],
    ],

];
