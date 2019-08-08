<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\EventDispatcher;

use Phly\EventDispatcher as Phly;
use Psr\EventDispatcher as Psr;
use Zend\ServiceManager\Proxy\LazyServiceFactory;

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
        'delegators' => [
            Psr\ListenerProviderInterface::class => [
                LazyServiceFactory::class,
            ],
        ],
        'lazy_services' => [
            'class_map' => [
                Psr\ListenerProviderInterface::class => Psr\ListenerProviderInterface::class,
            ],
        ],
    ],

];
