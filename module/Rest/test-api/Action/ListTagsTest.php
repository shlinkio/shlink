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
        yield 'admin API key without stats' => ['valid_api_key', [], [
            'data' => ['bar', 'baz', 'foo'],
        ]];
        yield 'admin API key with stats' => ['valid_api_key', ['withStats' => 'true'], [
            'data' => ['bar', 'baz', 'foo'],
            'stats' => [
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
        ]];

        yield 'author API key without stats' => ['author_api_key', [], [
            'data' => ['bar', 'foo'],
        ]];
        yield 'author API key with stats' => ['author_api_key', ['withStats' => 'true'], [
            'data' => ['bar', 'foo'],
            'stats' => [
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
        ]];

        yield 'domain API key without stats' => ['domain_api_key', [], [
            'data' => ['foo'],
        ]];
        yield 'domain API key with stats' => ['domain_api_key', ['withStats' => 'true'], [
            'data' => ['foo'],
            'stats' => [
                [
                    'tag' => 'foo',
                    'shortUrlsCount' => 1,
                    'visitsCount' => 0,
                ],
            ],
        ]];
    }
}
