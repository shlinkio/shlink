<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core;

use Phly\Swoole\TaskWorker\DeferredListenerDelegator;
use Shlinkio\Shlink\Common\IpGeolocation\IpLocationResolverInterface;
use Zend\ServiceManager\AbstractFactory\ConfigAbstractFactory;

return [

    'events' => [
        EventDispatcher\ShortUrlVisited::class => [
            EventDispatcher\LocateShortUrlVisit::class,
        ],
    ],

    'dependencies' => [
        'factories' => [
            EventDispatcher\LocateShortUrlVisit::class => ConfigAbstractFactory::class,
        ],
        'delegators' => [
            EventDispatcher\LocateShortUrlVisit::class => [
                DeferredListenerDelegator::class,
            ],
        ],
    ],

    ConfigAbstractFactory::class => [
        EventDispatcher\LocateShortUrlVisit::class => [IpLocationResolverInterface::class, 'em', 'Logger_Shlink'],
    ],

];
