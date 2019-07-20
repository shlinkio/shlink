<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\EventDispatcher;

use Swoole\Http\Server as HttpServer;

return [

    'dependencies' => [
        'factories' => [
            Async\TaskRunner::class => Async\TaskRunnerFactory::class,
        ],
        'delegators' => [
            HttpServer::class => [
                Async\TaskRunnerDelegator::class,
            ],
        ],
    ],

];
