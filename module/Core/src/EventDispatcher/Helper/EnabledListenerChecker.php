<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher\Helper;

use Shlinkio\Shlink\Common\Mercure\MercureOptions;
use Shlinkio\Shlink\Core\EventDispatcher;
use Shlinkio\Shlink\Core\Options\RabbitMqOptions;
use Shlinkio\Shlink\Core\Options\WebhookOptions;
use Shlinkio\Shlink\EventDispatcher\Listener\EnabledListenerCheckerInterface;
use Shlinkio\Shlink\IpGeolocation\GeoLite2\GeoLite2Options;

class EnabledListenerChecker implements EnabledListenerCheckerInterface
{
    public function __construct(
        private readonly RabbitMqOptions $rabbitMqOptions,
        private readonly bool $redisPubSubEnabled,
        private readonly MercureOptions $mercureOptions,
        private readonly WebhookOptions $webhookOptions,
        private readonly GeoLite2Options $geoLiteOptions,
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
            EventDispatcher\NotifyVisitToWebHooks::class => $this->webhookOptions->hasWebhooks(),
            EventDispatcher\UpdateGeoLiteDb::class => $this->geoLiteOptions->hasLicenseKey(),
            default => false, // Any unknown async listener should not be enabled by default
        };
    }
}
