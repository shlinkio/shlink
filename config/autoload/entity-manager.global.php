<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Common;

return [

    'entity_manager' => [
        'orm' => [
            'proxies_dir' => 'data/proxies',
        ],
        'connection' => [
            'user' => env('DB_USER'),
            'password' => env('DB_PASSWORD'),
            'dbname' => env('DB_NAME', 'shlink'),
            'charset' => 'utf8',
        ],
    ],

];
