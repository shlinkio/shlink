<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use GuzzleHttp\RequestOptions;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

use function sprintf;

class TagVisitsTest extends ApiTestCase
{
    /**
     * @test
     * @dataProvider provideTags
     */
    public function expectedVisitsAreReturned(
        string $apiKey,
        string $tag,
        bool $excludeBots,
        int $expectedVisitsAmount,
    ): void {
        $resp = $this->callApiWithKey(self::METHOD_GET, sprintf('/tags/%s/visits', $tag), [
            RequestOptions::QUERY => $excludeBots ? ['excludeBots' => true] : [],
        ], $apiKey);
        $payload = $this->getJsonResponsePayload($resp);

        self::assertEquals(self::STATUS_OK, $resp->getStatusCode());
        self::assertArrayHasKey('visits', $payload);
        self::assertArrayHasKey('data', $payload['visits']);
        self::assertCount($expectedVisitsAmount, $payload['visits']['data']);
    }

    public function provideTags(): iterable
    {
        yield 'foo with admin API key' => ['valid_api_key', 'foo', false, 5];
        yield 'foo with admin API key and no bots' => ['valid_api_key', 'foo', true, 4];
        yield 'bar with admin API key' => ['valid_api_key', 'bar', false, 2];
        yield 'bar with admin API key and no bots' => ['valid_api_key', 'bar', true, 1];
        yield 'baz with admin API key' => ['valid_api_key', 'baz', false, 0];
        yield 'foo with author API key' => ['author_api_key', 'foo', false, 5];
        yield 'foo with author API key and no bots' => ['author_api_key', 'foo', true, 4];
        yield 'bar with author API key' => ['author_api_key', 'bar', false, 2];
        yield 'bar with author API key and no bots' => ['author_api_key', 'bar', true, 1];
        yield 'foo with domain API key' => ['domain_api_key', 'foo', false, 0];
    }

    /**
     * @test
     * @dataProvider provideApiKeysAndTags
     */
    public function notFoundErrorIsReturnedForInvalidTags(string $apiKey, string $tag): void
    {
        $resp = $this->callApiWithKey(self::METHOD_GET, sprintf('/tags/%s/visits', $tag), [], $apiKey);
        $payload = $this->getJsonResponsePayload($resp);

        self::assertEquals(self::STATUS_NOT_FOUND, $resp->getStatusCode());
        self::assertEquals(self::STATUS_NOT_FOUND, $payload['status']);
        self::assertEquals('TAG_NOT_FOUND', $payload['type']);
        self::assertEquals(sprintf('Tag with name "%s" could not be found', $tag), $payload['detail']);
        self::assertEquals('Tag not found', $payload['title']);
    }

    public function provideApiKeysAndTags(): iterable
    {
        yield 'admin API key with invalid tag' => ['valid_api_key', 'invalid_tag'];
        yield 'domain API key with valid tag not used' => ['domain_api_key', 'bar'];
        yield 'author API key with valid tag not used' => ['author_api_key', 'baz'];
    }
}
