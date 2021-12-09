<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use GuzzleHttp\Psr7\Query;
use Laminas\Diactoros\Uri;
use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;
use ShlinkioApiTest\Shlink\Rest\Utils\NotFoundUrlHelpersTrait;

use function sprintf;

class ShortUrlVisitsTest extends ApiTestCase
{
    use NotFoundUrlHelpersTrait;

    /**
     * @test
     * @dataProvider provideInvalidUrls
     */
    public function tryingToGetVisitsForInvalidUrlReturnsNotFoundError(
        string $shortCode,
        ?string $domain,
        string $expectedDetail,
        string $apiKey,
    ): void {
        $resp = $this->callApiWithKey(
            self::METHOD_GET,
            $this->buildShortUrlPath($shortCode, $domain, '/visits'),
            [],
            $apiKey,
        );
        $payload = $this->getJsonResponsePayload($resp);

        self::assertEquals(self::STATUS_NOT_FOUND, $resp->getStatusCode());
        self::assertEquals(self::STATUS_NOT_FOUND, $payload['status']);
        self::assertEquals('INVALID_SHORTCODE', $payload['type']);
        self::assertEquals($expectedDetail, $payload['detail']);
        self::assertEquals('Short URL not found', $payload['title']);
        self::assertEquals($shortCode, $payload['shortCode']);
        self::assertEquals($domain, $payload['domain'] ?? null);
    }

    /**
     * @test
     * @dataProvider provideDomains
     */
    public function properVisitsAreReturnedWhenDomainIsProvided(?string $domain, int $expectedAmountOfVisits): void
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

    public function provideDomains(): iterable
    {
        yield 'domain' => ['example.com', 0];
        yield 'no domain' => [null, 2];
    }

    /**
     * @test
     * @dataProvider provideVisitsForBots
     */
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

    public function provideVisitsForBots(): iterable
    {
        yield 'bots excluded' => [true, 1];
        yield 'bots not excluded' => [false, 2];
    }
}
