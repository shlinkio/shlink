<?php

declare(strict_types=1);

return [

    'mezzio-swoole' => [
        'enable_coroutine' => true,

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
