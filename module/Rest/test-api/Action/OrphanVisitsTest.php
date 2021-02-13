<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use GuzzleHttp\RequestOptions;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

class OrphanVisitsTest extends ApiTestCase
{
    private const INVALID_SHORT_URL = [
        'referer' => 'https://doma.in/foo',
        'date' => '2020-03-01T00:00:00+00:00',
        'userAgent' => 'shlink-tests-agent',
        'visitLocation' => null,
        'visitedUrl' => 'foo.com',
        'type' => 'invalid_short_url',

    ];
    private const REGULAR_NOT_FOUND = [
        'referer' => 'https://doma.in/foo/bar',
        'date' => '2020-02-01T00:00:00+00:00',
        'userAgent' => 'shlink-tests-agent',
        'visitLocation' => null,
        'visitedUrl' => '',
        'type' => 'regular_404',
    ];
    private const BASE_URL = [
        'referer' => 'https://doma.in',
        'date' => '2020-01-01T00:00:00+00:00',
        'userAgent' => 'shlink-tests-agent',
        'visitLocation' => null,
        'visitedUrl' => '',
        'type' => 'base_url',
    ];

    /**
     * @test
     * @dataProvider provideQueries
     */
    public function properVisitsAreReturnedBasedInQuery(array $query, int $expectedAmount, array $expectedVisits): void
    {
        $resp = $this->callApiWithKey(self::METHOD_GET, '/visits/orphan', [RequestOptions::QUERY => $query]);
        $payload = $this->getJsonResponsePayload($resp);
        $visits = $payload['visits']['data'] ?? [];

        self::assertEquals(3, $payload['visits']['pagination']['totalItems'] ?? -1);
        self::assertCount($expectedAmount, $visits);
        self::assertEquals($expectedVisits, $visits);
    }

    public function provideQueries(): iterable
    {
        yield 'all data' => [[], 3, [self::INVALID_SHORT_URL, self::REGULAR_NOT_FOUND, self::BASE_URL]];
        yield 'limit items' => [['itemsPerPage' => 2], 2, [self::INVALID_SHORT_URL, self::REGULAR_NOT_FOUND]];
        yield 'limit items and page' => [['itemsPerPage' => 2, 'page' => 2], 1, [self::BASE_URL]];
    }
}
