<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core;

use Laminas\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Psr\EventDispatcher\EventDispatcherInterface;
use Shlinkio\Shlink\CLI\GeoLite\GeolocationDbUpdater;
use Shlinkio\Shlink\Common\Cache\RedisPublishingHelper;
use Shlinkio\Shlink\Common\Mercure\MercureHubPublishingHelper;
use Shlinkio\Shlink\Common\RabbitMq\RabbitMqPublishingHelper;
use Shlinkio\Shlink\Core\Visit\Geolocation\VisitLocator;
use Shlinkio\Shlink\Core\Visit\Geolocation\VisitToLocationHelper;
use Shlinkio\Shlink\IpGeolocation\GeoLite2\DbUpdater;
use Shlinkio\Shlink\IpGeolocation\Resolver\IpLocationResolverInterface;

return [

    'events' => [
        'regular' => [
            EventDispatcher\Event\UrlVisited::class => [
                EventDispatcher\LocateVisit::class,
            ],
            EventDispatcher\Event\GeoLiteDbCreated::class => [
                EventDispatcher\LocateUnlocatedVisits::class,
            ],
        ],
        'async' => [
            EventDispatcher\Event\VisitLocated::class => [
                EventDispatcher\Mercure\NotifyVisitToMercure::class,
                EventDispatcher\RabbitMq\NotifyVisitToRabbitMq::class,
                EventDispatcher\RedisPubSub\NotifyVisitToRedis::class,
                EventDispatcher\NotifyVisitToWebHooks::class,
                EventDispatcher\UpdateGeoLiteDb::class,
            ],
            EventDispatcher\Event\ShortUrlCreated::class => [
                EventDispatcher\Mercure\NotifyNewShortUrlToMercure::class,
                EventDispatcher\RabbitMq\NotifyNewShortUrlToRabbitMq::class,
                EventDispatcher\RedisPubSub\NotifyNewShortUrlToRedis::class,
            ],
        ],
    ],

    'dependencies' => [
        'factories' => [
            EventDispatcher\LocateVisit::class => ConfigAbstractFactory::class,
            EventDispatcher\LocateUnlocatedVisits::class => ConfigAbstractFactory::class,
            EventDispatcher\NotifyVisitToWebHooks::class => ConfigAbstractFactory::class,
            EventDispatcher\Mercure\NotifyVisitToMercure::class => ConfigAbstractFactory::class,
            EventDispatcher\Mercure\NotifyNewShortUrlToMercure::class => ConfigAbstractFactory::class,
            EventDispatcher\RabbitMq\NotifyVisitToRabbitMq::class => ConfigAbstractFactory::class,
            EventDispatcher\RabbitMq\NotifyNewShortUrlToRabbitMq::class => ConfigAbstractFactory::class,
            EventDispatcher\RedisPubSub\NotifyVisitToRedis::class => ConfigAbstractFactory::class,
            EventDispatcher\RedisPubSub\NotifyNewShortUrlToRedis::class => ConfigAbstractFactory::class,
            EventDispatcher\UpdateGeoLiteDb::class => ConfigAbstractFactory::class,
        ],

        'delegators' => [
            EventDispatcher\Mercure\NotifyVisitToMercure::class => [
                EventDispatcher\CloseDbConnectionEventListenerDelegator::class,
            ],
            EventDispatcher\Mercure\NotifyNewShortUrlToMercure::class => [
                EventDispatcher\CloseDbConnectionEventListenerDelegator::class,
            ],
            EventDispatcher\RabbitMq\NotifyVisitToRabbitMq::class => [
                EventDispatcher\CloseDbConnectionEventListenerDelegator::class,
            ],
            EventDispatcher\RabbitMq\NotifyNewShortUrlToRabbitMq::class => [
                EventDispatcher\CloseDbConnectionEventListenerDelegator::class,
            ],
            EventDispatcher\RedisPubSub\NotifyVisitToRedis::class => [
                EventDispatcher\CloseDbConnectionEventListenerDelegator::class,
            ],
            EventDispatcher\RedisPubSub\NotifyNewShortUrlToRedis::class => [
                EventDispatcher\CloseDbConnectionEventListenerDelegator::class,
            ],
            EventDispatcher\LocateUnlocatedVisits::class => [
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
        EventDispatcher\LocateUnlocatedVisits::class => [VisitLocator::class, VisitToLocationHelper::class],
        EventDispatcher\NotifyVisitToWebHooks::class => [
            'httpClient',
            'em',
            'Logger_Shlink',
            Options\WebhookOptions::class,
            ShortUrl\Transformer\ShortUrlDataTransformer::class,
            Options\AppOptions::class,
        ],
        EventDispatcher\Mercure\NotifyVisitToMercure::class => [
            MercureHubPublishingHelper::class,
            EventDispatcher\PublishingUpdatesGenerator::class,
            'em',
            'Logger_Shlink',
        ],
        EventDispatcher\Mercure\NotifyNewShortUrlToMercure::class => [
            MercureHubPublishingHelper::class,
            EventDispatcher\PublishingUpdatesGenerator::class,
            'em',
            'Logger_Shlink',
        ],
        EventDispatcher\RabbitMq\NotifyVisitToRabbitMq::class => [
            RabbitMqPublishingHelper::class,
            EventDispatcher\PublishingUpdatesGenerator::class,
            'em',
            'Logger_Shlink',
            Visit\Transformer\OrphanVisitDataTransformer::class,
            Options\RabbitMqOptions::class,
        ],
        EventDispatcher\RabbitMq\NotifyNewShortUrlToRabbitMq::class => [
            RabbitMqPublishingHelper::class,
            EventDispatcher\PublishingUpdatesGenerator::class,
            'em',
            'Logger_Shlink',
            Options\RabbitMqOptions::class,
        ],
        EventDispatcher\RedisPubSub\NotifyVisitToRedis::class => [
            RedisPublishingHelper::class,
            EventDispatcher\PublishingUpdatesGenerator::class,
            'em',
            'Logger_Shlink',
            'config.redis.pub_sub_enabled',
        ],
        EventDispatcher\RedisPubSub\NotifyNewShortUrlToRedis::class => [
            RedisPublishingHelper::class,
            EventDispatcher\PublishingUpdatesGenerator::class,
            'em',
            'Logger_Shlink',
            'config.redis.pub_sub_enabled',
        ],
        EventDispatcher\UpdateGeoLiteDb::class => [
            GeolocationDbUpdater::class,
            'Logger_Shlink',
            EventDispatcherInterface::class,
        ],
    ],

];
