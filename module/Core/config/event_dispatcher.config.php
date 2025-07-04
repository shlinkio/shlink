<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core;

use Laminas\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Psr\EventDispatcher\EventDispatcherInterface;
use Shlinkio\Shlink\Common\Cache\RedisPublishingHelper;
use Shlinkio\Shlink\Common\Mercure\MercureHubPublishingHelper;
use Shlinkio\Shlink\Common\Mercure\MercureOptions;
use Shlinkio\Shlink\Common\RabbitMq\RabbitMqPublishingHelper;
use Shlinkio\Shlink\Core\Geolocation\GeolocationDbUpdater;
use Shlinkio\Shlink\Core\Matomo\MatomoOptions;
use Shlinkio\Shlink\Core\Visit\Geolocation\VisitLocator;
use Shlinkio\Shlink\Core\Visit\Geolocation\VisitToLocationHelper;
use Shlinkio\Shlink\EventDispatcher\Listener\EnabledListenerCheckerInterface;
use Shlinkio\Shlink\IpGeolocation\GeoLite2\GeoLite2Options;

use function Shlinkio\Shlink\Config\runningInRoadRunner;

return (static function (): array {
    $regularEvents = [
        EventDispatcher\Event\GeoLiteDbCreated::class => [
            EventDispatcher\LocateUnlocatedVisits::class,
        ],
    ];
    $asyncEvents = [
        EventDispatcher\Event\UrlVisited::class => [
            EventDispatcher\Mercure\NotifyVisitToMercure::class,
            EventDispatcher\RabbitMq\NotifyVisitToRabbitMq::class,
            EventDispatcher\RedisPubSub\NotifyVisitToRedis::class,
            EventDispatcher\UpdateGeoLiteDb::class,
        ],
        EventDispatcher\Event\ShortUrlCreated::class => [
            EventDispatcher\Mercure\NotifyNewShortUrlToMercure::class,
            EventDispatcher\RabbitMq\NotifyNewShortUrlToRabbitMq::class,
            EventDispatcher\RedisPubSub\NotifyNewShortUrlToRedis::class,
        ],
    ];

    // Send visits to matomo asynchronously if the runtime allows it
    if (runningInRoadRunner()) {
        $asyncEvents[EventDispatcher\Event\UrlVisited::class][] = EventDispatcher\Matomo\SendVisitToMatomo::class;
    } else {
        $regularEvents[EventDispatcher\Event\UrlVisited::class] = [EventDispatcher\Matomo\SendVisitToMatomo::class];
    }

    return [

        'events' => [
            'regular' => $regularEvents,
            'async' => $asyncEvents,
        ],

        'dependencies' => [
            'factories' => [
                EventDispatcher\Matomo\SendVisitToMatomo::class => ConfigAbstractFactory::class,
                EventDispatcher\LocateUnlocatedVisits::class => ConfigAbstractFactory::class,
                EventDispatcher\Mercure\NotifyVisitToMercure::class => ConfigAbstractFactory::class,
                EventDispatcher\Mercure\NotifyNewShortUrlToMercure::class => ConfigAbstractFactory::class,
                EventDispatcher\RabbitMq\NotifyVisitToRabbitMq::class => ConfigAbstractFactory::class,
                EventDispatcher\RabbitMq\NotifyNewShortUrlToRabbitMq::class => ConfigAbstractFactory::class,
                EventDispatcher\RedisPubSub\NotifyVisitToRedis::class => ConfigAbstractFactory::class,
                EventDispatcher\RedisPubSub\NotifyNewShortUrlToRedis::class => ConfigAbstractFactory::class,
                EventDispatcher\UpdateGeoLiteDb::class => ConfigAbstractFactory::class,

                EventDispatcher\Helper\EnabledListenerChecker::class => ConfigAbstractFactory::class,
            ],

            'aliases' => [
                EnabledListenerCheckerInterface::class => EventDispatcher\Helper\EnabledListenerChecker::class,
            ],

            'delegators' => [
                EventDispatcher\Matomo\SendVisitToMatomo::class => [
                    EventDispatcher\CloseDbConnectionEventListenerDelegator::class,
                ],
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
                EventDispatcher\UpdateGeoLiteDb::class => [
                    EventDispatcher\CloseDbConnectionEventListenerDelegator::class,
                ],
            ],
        ],

        ConfigAbstractFactory::class => [
            EventDispatcher\LocateUnlocatedVisits::class => [VisitLocator::class, VisitToLocationHelper::class],
            EventDispatcher\Mercure\NotifyVisitToMercure::class => [
                MercureHubPublishingHelper::class,
                EventDispatcher\PublishingUpdatesGenerator::class,
                'em',
                'Logger_Shlink',
                Config\Options\RealTimeUpdatesOptions::class,
            ],
            EventDispatcher\Mercure\NotifyNewShortUrlToMercure::class => [
                MercureHubPublishingHelper::class,
                EventDispatcher\PublishingUpdatesGenerator::class,
                'em',
                'Logger_Shlink',
                Config\Options\RealTimeUpdatesOptions::class,
            ],
            EventDispatcher\RabbitMq\NotifyVisitToRabbitMq::class => [
                RabbitMqPublishingHelper::class,
                EventDispatcher\PublishingUpdatesGenerator::class,
                'em',
                'Logger_Shlink',
                Config\Options\RealTimeUpdatesOptions::class,
                Config\Options\RabbitMqOptions::class,
            ],
            EventDispatcher\RabbitMq\NotifyNewShortUrlToRabbitMq::class => [
                RabbitMqPublishingHelper::class,
                EventDispatcher\PublishingUpdatesGenerator::class,
                'em',
                'Logger_Shlink',
                Config\Options\RealTimeUpdatesOptions::class,
                Config\Options\RabbitMqOptions::class,
            ],
            EventDispatcher\RedisPubSub\NotifyVisitToRedis::class => [
                RedisPublishingHelper::class,
                EventDispatcher\PublishingUpdatesGenerator::class,
                'em',
                'Logger_Shlink',
                Config\Options\RealTimeUpdatesOptions::class,
                'config.redis.pub_sub_enabled',
            ],
            EventDispatcher\RedisPubSub\NotifyNewShortUrlToRedis::class => [
                RedisPublishingHelper::class,
                EventDispatcher\PublishingUpdatesGenerator::class,
                'em',
                'Logger_Shlink',
                Config\Options\RealTimeUpdatesOptions::class,
                'config.redis.pub_sub_enabled',
            ],

            EventDispatcher\Matomo\SendVisitToMatomo::class => [
                'em',
                'Logger_Shlink',
                Matomo\MatomoOptions::class,
                Matomo\MatomoVisitSender::class,
            ],

            EventDispatcher\UpdateGeoLiteDb::class => [
                GeolocationDbUpdater::class,
                'Logger_Shlink',
                EventDispatcherInterface::class,
            ],

            EventDispatcher\Helper\EnabledListenerChecker::class => [
                Config\Options\RabbitMqOptions::class,
                'config.redis.pub_sub_enabled',
                MercureOptions::class,
                GeoLite2Options::class,
                MatomoOptions::class,
            ],
        ],

    ];
})();
