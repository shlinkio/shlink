<?php

namespace ShlinkioTest\Shlink\Core\Matomo;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Exception\RuntimeException;
use Shlinkio\Shlink\Core\Matomo\MatomoOptions;
use Shlinkio\Shlink\Core\Matomo\MatomoTrackerBuilder;

class MatomoTrackerBuilderTest extends TestCase
{
    #[Test, DataProvider('provideInvalidOptions')]
    public function exceptionIsThrowsIfSomeParamIsMissing(MatomoOptions $options): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Cannot create MatomoTracker. Either site ID, base URL or api token are not defined',
        );
        $this->builder($options)->buildMatomoTracker();
    }

    public static function provideInvalidOptions(): iterable
    {
        yield [new MatomoOptions()];
        yield [new MatomoOptions(baseUrl: 'base_url')];
        yield [new MatomoOptions(apiToken: 'api_token')];
        yield [new MatomoOptions(siteId: 5)];
        yield [new MatomoOptions(baseUrl: 'base_url', apiToken: 'api_token')];
        yield [new MatomoOptions(baseUrl: 'base_url', siteId: 5)];
        yield [new MatomoOptions(siteId: 5, apiToken: 'api_token')];
    }

    #[Test]
    public function trackerIsCreated(): void
    {
        $tracker = $this->builder()->buildMatomoTracker();

        self::assertEquals('api_token', $tracker->token_auth); // @phpstan-ignore-line
        self::assertEquals(5, $tracker->idSite); // @phpstan-ignore-line
    }

    private function builder(?MatomoOptions $options = null): MatomoTrackerBuilder
    {
        $options ??= new MatomoOptions(enabled: true, baseUrl: 'base_url', siteId: 5, apiToken: 'api_token');
        return new MatomoTrackerBuilder($options);
    }
}
