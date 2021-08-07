<?php

declare(strict_types=1);

namespace Shlinkio\Shlink;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

return [

    'logger' => [
        'Shlink' => [
            'handlers' => [
                'shlink_handler' => [
                    'name' => StreamHandler::class,
                    'params' => [
                        'level' => Logger::INFO,
                        'stream' => 'php://stdout',
                    ],
                ],
            ],
        ],
    ],

];
