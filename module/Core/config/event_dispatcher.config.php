<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core;

use Laminas\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Psr\EventDispatcher\EventDispatcherInterface;
use Shlinkio\Shlink\CLI\Util\GeolocationDbUpdater;
use Shlinkio\Shlink\IpGeolocation\Resolver\IpLocationResolverInterface;
use Symfony\Component\Mercure\Publisher;

return [

    'events' => [
        'regular' => [
            EventDispatcher\Event\VisitLocated::class => [
                EventDispatcher\NotifyVisitToMercure::class,
                EventDispatcher\NotifyVisitToWebHooks::class,
            ],
        ],
        'async' => [
            EventDispatcher\Event\ShortUrlVisited::class => [
                EventDispatcher\LocateShortUrlVisit::class,
            ],
        ],
    ],

    'dependencies' => [
        'factories' => [
            EventDispatcher\LocateShortUrlVisit::class => ConfigAbstractFactory::class,
            EventDispatcher\NotifyVisitToWebHooks::class => ConfigAbstractFactory::class,
            EventDispatcher\NotifyVisitToMercure::class => ConfigAbstractFactory::class,
        ],

        'delegators' => [
            EventDispatcher\LocateShortUrlVisit::class => [
                EventDispatcher\CloseDbConnectionEventListenerDelegator::class,
            ],
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
            Options\AppOptions::class,
        ],
        EventDispatcher\NotifyVisitToMercure::class => [
            Publisher::class,
            Mercure\MercureUpdatesGenerator::class,
            'em',
            'Logger_Shlink',
        ],
    ],

];
