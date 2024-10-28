<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use GuzzleHttp\Psr7\Query;
use Laminas\Diactoros\Uri;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;
use ShlinkioApiTest\Shlink\Rest\Utils\ApiTestDataProviders;
use ShlinkioApiTest\Shlink\Rest\Utils\UrlBuilder;

use function sprintf;

class ShortUrlVisitsTest extends ApiTestCase
{
    #[Test, DataProviderExternal(ApiTestDataProviders::class, 'invalidUrlsProvider')]
    public function tryingToGetVisitsForInvalidUrlReturnsNotFoundError(
        string $shortCode,
        string|null $domain,
        string $expectedDetail,
        string $apiKey,
    ): void {
        $resp = $this->callApiWithKey(
            self::METHOD_GET,
            UrlBuilder::buildShortUrlPath($shortCode, $domain, '/visits'),
            apiKey: $apiKey,
        );
        $payload = $this->getJsonResponsePayload($resp);

        self::assertEquals(self::STATUS_NOT_FOUND, $resp->getStatusCode());
        self::assertEquals(self::STATUS_NOT_FOUND, $payload['status']);
        self::assertEquals('https://shlink.io/api/error/short-url-not-found', $payload['type']);
        self::assertEquals($expectedDetail, $payload['detail']);
        self::assertEquals('Short URL not found', $payload['title']);
        self::assertEquals($shortCode, $payload['shortCode']);
        self::assertEquals($domain, $payload['domain'] ?? null);
    }

    #[Test, DataProvider('provideDomains')]
    public function properVisitsAreReturnedWhenDomainIsProvided(string|null $domain, int $expectedAmountOfVisits): void
    {
        $shortCode = 'ghi789';
        $url = new Uri(sprintf('/short-urls/%s/visits', $shortCode));

        if ($domain !== null) {
            $url = $url->withQuery(Query::build(['domain' => $domain]));
        }

        $resp = $this->callApiWithKey(self::METHOD_GET, (string) $url);
        $payload = $this->getJsonResponsePayload($resp);

        self::assertEquals(
            $expectedAmountOfVisits,
            $payload['visits']['pagination']['totalItems'] ?? Paginator::ALL_ITEMS,
        );
        self::assertCount($expectedAmountOfVisits, $payload['visits']['data'] ?? []);
    }

    public static function provideDomains(): iterable
    {
        yield 'domain' => ['example.com', 0];
        yield 'no domain' => [null, 2];
    }

    #[Test, DataProvider('provideVisitsForBots')]
    public function properVisitsAreReturnedWhenExcludingBots(bool $excludeBots, int $expectedAmountOfVisits): void
    {
        $shortCode = 'def456';
        $url = new Uri(sprintf('/short-urls/%s/visits', $shortCode));

        if ($excludeBots) {
            $url = $url->withQuery(Query::build(['excludeBots' => true]));
        }

        $resp = $this->callApiWithKey(self::METHOD_GET, (string) $url);
        $payload = $this->getJsonResponsePayload($resp);

        self::assertEquals(
            $expectedAmountOfVisits,
            $payload['visits']['pagination']['totalItems'] ?? Paginator::ALL_ITEMS,
        );
        self::assertCount($expectedAmountOfVisits, $payload['visits']['data'] ?? []);
    }

    public static function provideVisitsForBots(): iterable
    {
        yield 'bots excluded' => [true, 1];
        yield 'bots not excluded' => [false, 2];
    }
}
