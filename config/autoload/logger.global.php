<?php
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

return [

    'logger' => [
        'formatters' => [
            'dashed' => [
                'format' => '[%datetime%] %channel%.%level_name% - %message% %context%' . PHP_EOL,
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

        'loggers' => [
            'Shlink' => [
                'handlers' => ['rotating_file_handler'],
            ],
        ],
    ],

];
