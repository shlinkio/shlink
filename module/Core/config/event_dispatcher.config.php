<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core;

use Psr\EventDispatcher\EventDispatcherInterface;
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
            EventDispatcher\VisitLocated::class => [
                EventDispatcher\NotifyVisitToWebHooks::class,
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
            EventDispatcherInterface::class,
        ],
        EventDispatcher\NotifyVisitToWebHooks::class => [
            'httpClient',
            'em',
            'Logger_Shlink',
            'config.url_shortener.visits_webhooks',
            'config.url_shortener.domain',
        ],
    ],

];
