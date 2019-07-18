<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common;

use Swoole\Http\Server as HttpServer;

return [

    'dependencies' => [
        'factories' => [
            EventDispatcher\TaskRunner::class => EventDispatcher\TaskRunnerFactory::class,
        ],
        'delegators' => [
            HttpServer::class => [
                EventDispatcher\TaskRunnerDelegator::class,
            ],
        ],
    ],

];
