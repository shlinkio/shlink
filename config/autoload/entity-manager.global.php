<?php
declare(strict_types=1);

use function Shlinkio\Shlink\Common\env;

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
