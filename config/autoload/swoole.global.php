<?php

declare(strict_types=1);

use Shlinkio\Shlink\Core\Config\EnvVars;

use const Shlinkio\Shlink\MIN_TASK_WORKERS;

return (static function () {
    $taskWorkers = (int) EnvVars::TASK_WORKER_NUM()->loadFromEnv(16);

    return [

        'mezzio-swoole' => [
            // Setting this to true can have unexpected behaviors when running several concurrent slow DB queries
            'enable_coroutine' => false,

            'swoole-http-server' => [
                'host' => '0.0.0.0',
                'port' => (int) EnvVars::PORT()->loadFromEnv(8080),
                'process-name' => 'shlink',

                'options' => [
                    'worker_num' => (int) EnvVars::WEB_WORKER_NUM()->loadFromEnv(16),
                    'task_worker_num' => max($taskWorkers, MIN_TASK_WORKERS),
                ],
            ],
        ],

    ];
})();
