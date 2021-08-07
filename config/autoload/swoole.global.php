<?php

declare(strict_types=1);

use function Shlinkio\Shlink\Common\env;

return [

    'mezzio-swoole' => [
        // Setting this to true can have unexpected behaviors when running several concurrent slow DB queries
        'enable_coroutine' => false,

        'swoole-http-server' => [
            'host' => '0.0.0.0',
            'port' => (int) env('PORT', 8080),
            'process-name' => 'shlink',

            'options' => [
                'worker_num' => (int) env('WEB_WORKER_NUM', 16),
                'task_worker_num' => (int) env('TASK_WORKER_NUM', 16),
            ],
        ],
    ],

];
