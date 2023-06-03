<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\EventDispatcher\Helper;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\EventDispatcher\Helper\EnabledListenerChecker;
use Shlinkio\Shlink\Core\EventDispatcher\Mercure\NotifyNewShortUrlToMercure;
use Shlinkio\Shlink\Core\EventDispatcher\Mercure\NotifyVisitToMercure;
use Shlinkio\Shlink\Core\EventDispatcher\NotifyVisitToWebHooks;
use Shlinkio\Shlink\Core\EventDispatcher\RabbitMq\NotifyNewShortUrlToRabbitMq;
use Shlinkio\Shlink\Core\EventDispatcher\RabbitMq\NotifyVisitToRabbitMq;
use Shlinkio\Shlink\Core\EventDispatcher\RedisPubSub\NotifyNewShortUrlToRedis;
use Shlinkio\Shlink\Core\EventDispatcher\RedisPubSub\NotifyVisitToRedis;
use Shlinkio\Shlink\Core\EventDispatcher\UpdateGeoLiteDb;
use Shlinkio\Shlink\Core\Options\RabbitMqOptions;
use Shlinkio\Shlink\Core\Options\WebhookOptions;
use Shlinkio\Shlink\IpGeolocation\GeoLite2\GeoLite2Options;

class EnabledListenerCheckerTest extends TestCase
{
    #[Test, DataProvider('provideListeners')]
    public function syncListenersAreRegisteredByDefault(string $listener): void
    {
        self::assertTrue($this->checker()->shouldRegisterListener('', $listener, false));
    }

    public static function provideListeners(): iterable
    {
        return [
            [NotifyVisitToRabbitMq::class],
            [NotifyNewShortUrlToRabbitMq::class],
            [NotifyVisitToRedis::class],
            [NotifyNewShortUrlToRedis::class],
            [NotifyVisitToMercure::class],
            [NotifyNewShortUrlToMercure::class],
            [NotifyVisitToWebHooks::class],
            [UpdateGeoLiteDb::class],
        ];
    }

    /**
     * @param array<string, boolean> $expectedResult
     */
    #[Test, DataProvider('provideConfiguredCheckers')]
    public function appropriateListenersAreEnabledBasedOnConfig(
        EnabledListenerChecker $checker,
        array $expectedResult,
    ): void {
        foreach ($expectedResult as $listener => $shouldBeRegistered) {
            self::assertEquals($shouldBeRegistered, $checker->shouldRegisterListener('', $listener, true));
        }
    }

    public static function provideConfiguredCheckers(): iterable
    {
        yield 'RabbitMQ' => [self::checker(rabbitMqEnabled: true), [
            NotifyVisitToRabbitMq::class => true,
            NotifyNewShortUrlToRabbitMq::class => true,
            NotifyVisitToRedis::class => false,
            NotifyNewShortUrlToRedis::class => false,
            NotifyVisitToMercure::class => false,
            NotifyNewShortUrlToMercure::class => false,
            NotifyVisitToWebHooks::class => false,
            UpdateGeoLiteDb::class => false,
            'unknown' => false,
        ]];
        yield 'Redis Pub/Sub' => [self::checker(redisPubSubEnabled: true), [
            NotifyVisitToRabbitMq::class => false,
            NotifyNewShortUrlToRabbitMq::class => false,
            NotifyVisitToRedis::class => true,
            NotifyNewShortUrlToRedis::class => true,
            NotifyVisitToMercure::class => false,
            NotifyNewShortUrlToMercure::class => false,
            NotifyVisitToWebHooks::class => false,
            UpdateGeoLiteDb::class => false,
            'unknown' => false,
        ]];
        yield 'Mercure' => [self::checker(mercureEnabled: true), [
            NotifyVisitToRabbitMq::class => false,
            NotifyNewShortUrlToRabbitMq::class => false,
            NotifyVisitToRedis::class => false,
            NotifyNewShortUrlToRedis::class => false,
            NotifyVisitToMercure::class => true,
            NotifyNewShortUrlToMercure::class => true,
            NotifyVisitToWebHooks::class => false,
            UpdateGeoLiteDb::class => false,
            'unknown' => false,
        ]];
        yield 'Webhooks' => [self::checker(webhooksEnabled: true), [
            NotifyVisitToRabbitMq::class => false,
            NotifyNewShortUrlToRabbitMq::class => false,
            NotifyVisitToRedis::class => false,
            NotifyNewShortUrlToRedis::class => false,
            NotifyVisitToMercure::class => false,
            NotifyNewShortUrlToMercure::class => false,
            NotifyVisitToWebHooks::class => true,
            UpdateGeoLiteDb::class => false,
            'unknown' => false,
        ]];
        yield 'GeoLite' => [self::checker(geoLiteEnabled: true), [
            NotifyVisitToRabbitMq::class => false,
            NotifyNewShortUrlToRabbitMq::class => false,
            NotifyVisitToRedis::class => false,
            NotifyNewShortUrlToRedis::class => false,
            NotifyVisitToMercure::class => false,
            NotifyNewShortUrlToMercure::class => false,
            NotifyVisitToWebHooks::class => false,
            UpdateGeoLiteDb::class => true,
            'unknown' => false,
        ]];
        yield 'All disabled' => [self::checker(), [
            NotifyVisitToRabbitMq::class => false,
            NotifyNewShortUrlToRabbitMq::class => false,
            NotifyVisitToRedis::class => false,
            NotifyNewShortUrlToRedis::class => false,
            NotifyVisitToMercure::class => false,
            NotifyNewShortUrlToMercure::class => false,
            NotifyVisitToWebHooks::class => false,
            UpdateGeoLiteDb::class => false,
            'unknown' => false,
        ]];
        yield 'All enabled' => [self::checker(
            rabbitMqEnabled: true,
            redisPubSubEnabled: true,
            mercureEnabled: true,
            webhooksEnabled: true,
            geoLiteEnabled: true,
        ), [
            NotifyVisitToRabbitMq::class => true,
            NotifyNewShortUrlToRabbitMq::class => true,
            NotifyVisitToRedis::class => true,
            NotifyNewShortUrlToRedis::class => true,
            NotifyVisitToMercure::class => true,
            NotifyNewShortUrlToMercure::class => true,
            NotifyVisitToWebHooks::class => true,
            UpdateGeoLiteDb::class => true,
            'unknown' => false,
        ]];
    }

    private static function checker(
        bool $rabbitMqEnabled = false,
        bool $redisPubSubEnabled = false,
        bool $mercureEnabled = false,
        bool $webhooksEnabled = false,
        bool $geoLiteEnabled = false,
    ): EnabledListenerChecker {
        return new EnabledListenerChecker(
            new RabbitMqOptions(enabled: $rabbitMqEnabled),
            $redisPubSubEnabled,
            $mercureEnabled ? 'the-url' : null,
            new WebhookOptions(['webhooks' => $webhooksEnabled ? ['foo', 'bar'] : []]),
            new GeoLite2Options(licenseKey: $geoLiteEnabled ? 'the-key' : null),
        );
    }
}
