<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use GuzzleHttp\RequestOptions;
use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

class OrphanVisitsTest extends ApiTestCase
{
    private const INVALID_SHORT_URL = [
        'referer' => 'https://doma.in/foo',
        'date' => '2020-03-01T00:00:00+00:00',
        'userAgent' => 'cf-facebook',
        'visitLocation' => null,
        'potentialBot' => true,
        'visitedUrl' => 'foo.com',
        'type' => 'invalid_short_url',
    ];
    private const REGULAR_NOT_FOUND = [
        'referer' => 'https://doma.in/foo/bar',
        'date' => '2020-02-01T00:00:00+00:00',
        'userAgent' => 'shlink-tests-agent',
        'visitLocation' => null,
        'potentialBot' => false,
        'visitedUrl' => '',
        'type' => 'regular_404',
    ];
    private const BASE_URL = [
        'referer' => 'https://doma.in',
        'date' => '2020-01-01T00:00:00+00:00',
        'userAgent' => 'shlink-tests-agent',
        'visitLocation' => null,
        'potentialBot' => false,
        'visitedUrl' => '',
        'type' => 'base_url',
    ];

    /**
     * @test
     * @dataProvider provideQueries
     */
    public function properVisitsAreReturnedBasedInQuery(
        array $query,
        int $totalItems,
        int $expectedAmount,
        array $expectedVisits,
    ): void {
        $resp = $this->callApiWithKey(self::METHOD_GET, '/visits/orphan', [RequestOptions::QUERY => $query]);
        $payload = $this->getJsonResponsePayload($resp);
        $visits = $payload['visits']['data'] ?? [];

        self::assertEquals($totalItems, $payload['visits']['pagination']['totalItems'] ?? Paginator::ALL_ITEMS);
        self::assertCount($expectedAmount, $visits);
        self::assertEquals($expectedVisits, $visits);
    }

    public function provideQueries(): iterable
    {
        yield 'all data' => [[], 3, 3, [self::INVALID_SHORT_URL, self::REGULAR_NOT_FOUND, self::BASE_URL]];
        yield 'limit items' => [['itemsPerPage' => 2], 3, 2, [self::INVALID_SHORT_URL, self::REGULAR_NOT_FOUND]];
        yield 'limit items and page' => [['itemsPerPage' => 2, 'page' => 2], 3, 1, [self::BASE_URL]];
        yield 'exclude bots' => [['excludeBots' => true], 2, 2, [self::REGULAR_NOT_FOUND, self::BASE_URL]];
        yield 'exclude bots and limit items' => [
            ['excludeBots' => true, 'itemsPerPage' => 1],
            2,
            1,
            [self::REGULAR_NOT_FOUND],
        ];
    }
}
