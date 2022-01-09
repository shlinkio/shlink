<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use GuzzleHttp\RequestOptions;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

class TagsStatsTest extends ApiTestCase
{
    /**
     * @test
     * @dataProvider provideQueries
     */
    public function expectedListOfTagsIsReturned(string $apiKey, array $query, array $expectedTags): void
    {
        $resp = $this->callApiWithKey(self::METHOD_GET, '/tags/stats', [RequestOptions::QUERY => $query], $apiKey);
        $payload = $this->getJsonResponsePayload($resp);

        self::assertEquals(['tags' => $expectedTags], $payload);
    }

    public function provideQueries(): iterable
    {
        yield 'admin API key' => ['valid_api_key', [], [
            'data' => [
                [
                    'tag' => 'bar',
                    'shortUrlsCount' => 1,
                    'visitsCount' => 2,
                ],
                [
                    'tag' => 'baz',
                    'shortUrlsCount' => 0,
                    'visitsCount' => 0,
                ],
                [
                    'tag' => 'foo',
                    'shortUrlsCount' => 3,
                    'visitsCount' => 5,
                ],
            ],
            'pagination' => [
                'currentPage' => 1,
                'pagesCount' => 1,
                'itemsPerPage' => 3,
                'itemsInCurrentPage' => 3,
                'totalItems' => 3,
            ],
        ]];
        yield 'admin API key with pagination' => ['valid_api_key', ['page' => 1, 'itemsPerPage' => 2], [
            'data' => [
                [
                    'tag' => 'bar',
                    'shortUrlsCount' => 1,
                    'visitsCount' => 2,
                ],
                [
                    'tag' => 'baz',
                    'shortUrlsCount' => 0,
                    'visitsCount' => 0,
                ],
            ],
            'pagination' => [
                'currentPage' => 1,
                'pagesCount' => 2,
                'itemsPerPage' => 2,
                'itemsInCurrentPage' => 2,
                'totalItems' => 3,
            ],
        ]];
        yield 'author API key' => ['author_api_key', [], [
            'data' => [
                [
                    'tag' => 'bar',
                    'shortUrlsCount' => 1,
                    'visitsCount' => 2,
                ],
                [
                    'tag' => 'foo',
                    'shortUrlsCount' => 2,
                    'visitsCount' => 5,
                ],
            ],
            'pagination' => [
                'currentPage' => 1,
                'pagesCount' => 1,
                'itemsPerPage' => 2,
                'itemsInCurrentPage' => 2,
                'totalItems' => 2,
            ],
        ]];
        yield 'author API key with pagination' => ['author_api_key', ['page' => 2, 'itemsPerPage' => 1], [
            'data' => [
                [
                    'tag' => 'foo',
                    'shortUrlsCount' => 2,
                    'visitsCount' => 5,
                ],
            ],
            'pagination' => [
                'currentPage' => 2,
                'pagesCount' => 2,
                'itemsPerPage' => 1,
                'itemsInCurrentPage' => 1,
                'totalItems' => 2,
            ],
        ]];
        yield 'domain API key' => ['domain_api_key', [], [
            'data' => [
                [
                    'tag' => 'foo',
                    'shortUrlsCount' => 1,
                    'visitsCount' => 0,
                ],
            ],
            'pagination' => [
                'currentPage' => 1,
                'pagesCount' => 1,
                'itemsPerPage' => 1,
                'itemsInCurrentPage' => 1,
                'totalItems' => 1,
            ],
        ]];
    }
}
