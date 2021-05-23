<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core;

use Laminas\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Psr\EventDispatcher\EventDispatcherInterface;
use Shlinkio\Shlink\CLI\Util\GeolocationDbUpdater;
use Shlinkio\Shlink\IpGeolocation\GeoLite2\DbUpdater;
use Shlinkio\Shlink\IpGeolocation\Resolver\IpLocationResolverInterface;
use Symfony\Component\Mercure\Hub;

return [

    'events' => [
        'regular' => [
            EventDispatcher\Event\UrlVisited::class => [
                EventDispatcher\LocateVisit::class,
            ],
        ],
        'async' => [
            EventDispatcher\Event\VisitLocated::class => [
                EventDispatcher\NotifyVisitToMercure::class,
                EventDispatcher\NotifyVisitToWebHooks::class,
                EventDispatcher\UpdateGeoLiteDb::class,
            ],
        ],
    ],

    'dependencies' => [
        'factories' => [
            EventDispatcher\LocateVisit::class => ConfigAbstractFactory::class,
            EventDispatcher\NotifyVisitToWebHooks::class => ConfigAbstractFactory::class,
            EventDispatcher\NotifyVisitToMercure::class => ConfigAbstractFactory::class,
            EventDispatcher\UpdateGeoLiteDb::class => ConfigAbstractFactory::class,
        ],

        'delegators' => [
            EventDispatcher\NotifyVisitToMercure::class => [
                EventDispatcher\CloseDbConnectionEventListenerDelegator::class,
            ],
            EventDispatcher\NotifyVisitToWebHooks::class => [
                EventDispatcher\CloseDbConnectionEventListenerDelegator::class,
            ],
        ],
    ],

    ConfigAbstractFactory::class => [
        EventDispatcher\LocateVisit::class => [
            IpLocationResolverInterface::class,
            'em',
            'Logger_Shlink',
            DbUpdater::class,
            EventDispatcherInterface::class,
        ],
        EventDispatcher\NotifyVisitToWebHooks::class => [
            'httpClient',
            'em',
            'Logger_Shlink',
            'config.url_shortener.visits_webhooks',
            ShortUrl\Transformer\ShortUrlDataTransformer::class,
            Options\AppOptions::class,
        ],
        EventDispatcher\NotifyVisitToMercure::class => [
            Hub::class,
            Mercure\MercureUpdatesGenerator::class,
            'em',
            'Logger_Shlink',
        ],
        EventDispatcher\UpdateGeoLiteDb::class => [GeolocationDbUpdater::class, 'Logger_Shlink'],
    ],

];
