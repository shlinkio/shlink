<?php
declare(strict_types=1);

use Shlinkio\Shlink\Common;

return [

    'entity_manager' => [
        'orm' => [
            'proxies_dir' => 'data/proxies',
        ],
        'connection' => [
            'user' => Common\env('DB_USER'),
            'password' => Common\env('DB_PASSWORD'),
            'dbname' => Common\env('DB_NAME', 'shlink'),
            'charset' => 'utf8',
        ],
    ],

];
