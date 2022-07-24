<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core;

use Laminas\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Psr\EventDispatcher\EventDispatcherInterface;
use Shlinkio\Shlink\CLI\Util\GeolocationDbUpdater;
use Shlinkio\Shlink\Common\RabbitMq\RabbitMqPublishingHelper;
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
                EventDispatcher\Mercure\NotifyVisitToMercure::class,
                EventDispatcher\RabbitMq\NotifyVisitToRabbitMq::class,
                EventDispatcher\NotifyVisitToWebHooks::class,
                EventDispatcher\UpdateGeoLiteDb::class,
            ],
            EventDispatcher\Event\ShortUrlCreated::class => [
                EventDispatcher\Mercure\NotifyNewShortUrlToMercure::class,
                EventDispatcher\RabbitMq\NotifyNewShortUrlToRabbitMq::class,
            ],
        ],
    ],

    'dependencies' => [
        'factories' => [
            EventDispatcher\LocateVisit::class => ConfigAbstractFactory::class,
            EventDispatcher\NotifyVisitToWebHooks::class => ConfigAbstractFactory::class,
            EventDispatcher\Mercure\NotifyVisitToMercure::class => ConfigAbstractFactory::class,
            EventDispatcher\RabbitMq\NotifyVisitToRabbitMq::class => ConfigAbstractFactory::class,
            EventDispatcher\RabbitMq\NotifyNewShortUrlToRabbitMq::class => ConfigAbstractFactory::class,
            EventDispatcher\UpdateGeoLiteDb::class => ConfigAbstractFactory::class,
        ],

        'delegators' => [
            EventDispatcher\Mercure\NotifyVisitToMercure::class => [
                EventDispatcher\CloseDbConnectionEventListenerDelegator::class,
            ],
            EventDispatcher\RabbitMq\NotifyVisitToRabbitMq::class => [
                EventDispatcher\CloseDbConnectionEventListenerDelegator::class,
            ],
            EventDispatcher\RabbitMq\NotifyNewShortUrlToRabbitMq::class => [
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
            Options\WebhookOptions::class,
            ShortUrl\Transformer\ShortUrlDataTransformer::class,
            Options\AppOptions::class,
        ],
        EventDispatcher\Mercure\NotifyVisitToMercure::class => [
            Hub::class,
            Mercure\MercureUpdatesGenerator::class,
            'em',
            'Logger_Shlink',
        ],
        EventDispatcher\RabbitMq\NotifyVisitToRabbitMq::class => [
            RabbitMqPublishingHelper::class,
            'em',
            'Logger_Shlink',
            Visit\Transformer\OrphanVisitDataTransformer::class,
            'config.rabbitmq.enabled',
        ],
        EventDispatcher\RabbitMq\NotifyNewShortUrlToRabbitMq::class => [
            RabbitMqPublishingHelper::class,
            'em',
            'Logger_Shlink',
            ShortUrl\Transformer\ShortUrlDataTransformer::class,
            'config.rabbitmq.enabled',
        ],
        EventDispatcher\UpdateGeoLiteDb::class => [GeolocationDbUpdater::class, 'Logger_Shlink'],
    ],

];
