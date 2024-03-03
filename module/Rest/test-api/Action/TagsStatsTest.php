<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

class TagsStatsTest extends ApiTestCase
{
    #[Test, DataProvider('provideQueries')]
    public function expectedListOfTagsIsReturned(
        string $apiKey,
        array $query,
        array $expectedStats,
        array $expectedPagination,
    ): void {
        $resp = $this->callApiWithKey(self::METHOD_GET, '/tags/stats', [RequestOptions::QUERY => $query], $apiKey);
        ['tags' => $tags] = $this->getJsonResponsePayload($resp);

        self::assertEquals($expectedStats, $tags['data']);
        self::assertEquals($expectedPagination, $tags['pagination']);
    }

    public static function provideQueries(): iterable
    {
        yield 'admin API key' => ['valid_api_key', [], [
            [
                'tag' => 'bar',
                'shortUrlsCount' => 1,
                'visitsSummary' => [
                    'total' => 2,
                    'nonBots' => 1,
                    'bots' => 1,
                ],
            ],
            [
                'tag' => 'baz',
                'shortUrlsCount' => 0,
                'visitsSummary' => [
                    'total' => 0,
                    'nonBots' => 0,
                    'bots' => 0,
                ],
            ],
            [
                'tag' => 'foo',
                'shortUrlsCount' => 3,
                'visitsSummary' => [
                    'total' => 5,
                    'nonBots' => 4,
                    'bots' => 1,
                ],
            ],
        ], [
            'currentPage' => 1,
            'pagesCount' => 1,
            'itemsPerPage' => 3,
            'itemsInCurrentPage' => 3,
            'totalItems' => 3,
        ]];
        yield 'admin API key with pagination' => ['valid_api_key', ['page' => 1, 'itemsPerPage' => 2], [
            [
                'tag' => 'bar',
                'shortUrlsCount' => 1,
                'visitsSummary' => [
                    'total' => 2,
                    'nonBots' => 1,
                    'bots' => 1,
                ],
            ],
            [
                'tag' => 'baz',
                'shortUrlsCount' => 0,
                'visitsSummary' => [
                    'total' => 0,
                    'nonBots' => 0,
                    'bots' => 0,
                ],
            ],
        ], [
            'currentPage' => 1,
            'pagesCount' => 2,
            'itemsPerPage' => 2,
            'itemsInCurrentPage' => 2,
            'totalItems' => 3,
        ]];
        yield 'author API key' => ['author_api_key', [], [
            [
                'tag' => 'bar',
                'shortUrlsCount' => 1,
                'visitsSummary' => [
                    'total' => 2,
                    'nonBots' => 1,
                    'bots' => 1,
                ],
            ],
            [
                'tag' => 'foo',
                'shortUrlsCount' => 2,
                'visitsSummary' => [
                    'total' => 5,
                    'nonBots' => 4,
                    'bots' => 1,
                ],
            ],
        ], [
            'currentPage' => 1,
            'pagesCount' => 1,
            'itemsPerPage' => 2,
            'itemsInCurrentPage' => 2,
            'totalItems' => 2,
        ]];
        yield 'author API key with pagination' => ['author_api_key', ['page' => 2, 'itemsPerPage' => 1], [
            [
                'tag' => 'foo',
                'shortUrlsCount' => 2,
                'visitsSummary' => [
                    'total' => 5,
                    'nonBots' => 4,
                    'bots' => 1,
                ],
            ],
        ], [
            'currentPage' => 2,
            'pagesCount' => 2,
            'itemsPerPage' => 1,
            'itemsInCurrentPage' => 1,
            'totalItems' => 2,
        ]];
        yield 'domain API key' => ['domain_api_key', [], [
            [
                'tag' => 'foo',
                'shortUrlsCount' => 1,
                'visitsSummary' => [
                    'total' => 0,
                    'nonBots' => 0,
                    'bots' => 0,
                ],
            ],
        ], [
            'currentPage' => 1,
            'pagesCount' => 1,
            'itemsPerPage' => 1,
            'itemsInCurrentPage' => 1,
            'totalItems' => 1,
        ]];
    }
}
