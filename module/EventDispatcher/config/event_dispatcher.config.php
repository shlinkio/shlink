<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\EventDispatcher;

use Phly\EventDispatcher as Phly;
use Psr\EventDispatcher as Psr;

return [

    'events' => [
        'regular' => [],
        'async' => [],
    ],

    'dependencies' => [
        'factories' => [
            Phly\EventDispatcher::class => Phly\EventDispatcherFactory::class,
            Psr\ListenerProviderInterface::class => Listener\ListenerProviderFactory::class,
        ],
        'aliases' => [
            Psr\EventDispatcherInterface::class => Phly\EventDispatcher::class,
        ],
    ],

];
