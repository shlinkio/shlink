<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use GuzzleHttp\RequestOptions;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

class ListTagsTest extends ApiTestCase
{
    /**
     * @test
     * @dataProvider provideQueries
     */
    public function expectedListOfTagsIsReturned(string $apiKey, array $query, array $expectedTags): void
    {
        $resp = $this->callApiWithKey(self::METHOD_GET, '/tags', [RequestOptions::QUERY => $query], $apiKey);
        $payload = $this->getJsonResponsePayload($resp);

        self::assertEquals(['tags' => $expectedTags], $payload);
    }

    public function provideQueries(): iterable
    {
        yield 'admin API key' => ['valid_api_key', [], [
            'data' => ['bar', 'baz', 'foo'],
            'pagination' => [
                'currentPage' => 1,
                'pagesCount' => 1,
                'itemsPerPage' => 3,
                'itemsInCurrentPage' => 3,
                'totalItems' => 3,
            ],
        ]];
        yield 'admin api key with pagination' => ['valid_api_key', ['page' => 2, 'itemsPerPage' => 2], [
            'data' => ['foo'],
            'pagination' => [
                'currentPage' => 2,
                'pagesCount' => 2,
                'itemsPerPage' => 2,
                'itemsInCurrentPage' => 1,
                'totalItems' => 3,
            ],
        ]];
        yield 'author API key' => ['author_api_key', [], [
            'data' => ['bar', 'foo'],
            'pagination' => [
                'currentPage' => 1,
                'pagesCount' => 1,
                'itemsPerPage' => 2,
                'itemsInCurrentPage' => 2,
                'totalItems' => 2,
            ],
        ]];
        yield 'domain API key' => ['domain_api_key', [], [
            'data' => ['foo'],
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
