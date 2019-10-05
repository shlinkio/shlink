<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core;

use Shlinkio\Shlink\CLI\Util\GeolocationDbUpdater;
use Shlinkio\Shlink\IpGeolocation\Resolver\IpLocationResolverInterface;
use Zend\ServiceManager\AbstractFactory\ConfigAbstractFactory;

return [

    'events' => [
        'regular' => [],
        'async' => [
            EventDispatcher\ShortUrlVisited::class => [
                EventDispatcher\LocateShortUrlVisit::class,
            ],
        ],
    ],

    'dependencies' => [
        'factories' => [
            EventDispatcher\LocateShortUrlVisit::class => ConfigAbstractFactory::class,
        ],
    ],

    ConfigAbstractFactory::class => [
        EventDispatcher\LocateShortUrlVisit::class => [
            IpLocationResolverInterface::class,
            'em',
            'Logger_Shlink',
            GeolocationDbUpdater::class,
        ],
    ],

];
