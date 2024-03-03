<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher\Helper;

use Shlinkio\Shlink\Common\Mercure\MercureOptions;
use Shlinkio\Shlink\Core\EventDispatcher;
use Shlinkio\Shlink\Core\Matomo\MatomoOptions;
use Shlinkio\Shlink\Core\Options\RabbitMqOptions;
use Shlinkio\Shlink\EventDispatcher\Listener\EnabledListenerCheckerInterface;
use Shlinkio\Shlink\IpGeolocation\GeoLite2\GeoLite2Options;

readonly class EnabledListenerChecker implements EnabledListenerCheckerInterface
{
    public function __construct(
        private RabbitMqOptions $rabbitMqOptions,
        private bool $redisPubSubEnabled,
        private MercureOptions $mercureOptions,
        private GeoLite2Options $geoLiteOptions,
        private MatomoOptions $matomoOptions,
    ) {
    }

    public function shouldRegisterListener(string $event, string $listener, bool $isAsync): bool
    {
        if (! $isAsync) {
            return true;
        }

        return match ($listener) {
            EventDispatcher\RabbitMq\NotifyVisitToRabbitMq::class,
            EventDispatcher\RabbitMq\NotifyNewShortUrlToRabbitMq::class => $this->rabbitMqOptions->enabled,
            EventDispatcher\RedisPubSub\NotifyVisitToRedis::class,
            EventDispatcher\RedisPubSub\NotifyNewShortUrlToRedis::class => $this->redisPubSubEnabled,
            EventDispatcher\Mercure\NotifyVisitToMercure::class,
            EventDispatcher\Mercure\NotifyNewShortUrlToMercure::class => $this->mercureOptions->isEnabled(),
            EventDispatcher\Matomo\SendVisitToMatomo::class => $this->matomoOptions->enabled,
            EventDispatcher\UpdateGeoLiteDb::class => $this->geoLiteOptions->hasLicenseKey(),
            default => false, // Any unknown async listener should not be enabled by default
        };
    }
}
