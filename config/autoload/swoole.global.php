<?php

declare(strict_types=1);

return [

    'mezzio-swoole' => [
        // Setting this to true can have unexpected behaviors when running several concurrent slow DB queries
        'enable_coroutine' => false,

        'swoole-http-server' => [
            'host' => '0.0.0.0',
            'process-name' => 'shlink',

            'options' => [
                'worker_num' => 16,
                'task_worker_num' => 16,
            ],
        ],
    ],

];
