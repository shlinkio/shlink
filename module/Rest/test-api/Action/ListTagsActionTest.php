<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use GuzzleHttp\RequestOptions;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

class ListTagsActionTest extends ApiTestCase
{
    /**
     * @test
     * @dataProvider provideQueries
     */
    public function expectedListOfTagsIsReturned(array $query, array $expectedTags): void
    {
        $resp = $this->callApiWithKey(self::METHOD_GET, '/tags', [RequestOptions::QUERY => $query]);
        $payload = $this->getJsonResponsePayload($resp);

        self::assertEquals(['tags' => $expectedTags], $payload);
    }

    public function provideQueries(): iterable
    {
        yield 'stats not requested' => [[], [
            'data' => ['bar', 'baz', 'foo'],
        ]];
        yield 'stats requested' => [['withStats' => 'true'], [
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
    }
}
