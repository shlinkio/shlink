<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

use function sprintf;

class TagVisitsTest extends ApiTestCase
{
    #[Test, DataProvider('provideTags')]
    public function expectedVisitsAreReturned(
        string $apiKey,
        string $tag,
        array $query,
        int $expectedVisitsAmount,
    ): void {
        $resp = $this->callApiWithKey(self::METHOD_GET, sprintf('/tags/%s/visits', $tag), [
            RequestOptions::QUERY => $query,
        ], $apiKey);
        $payload = $this->getJsonResponsePayload($resp);

        self::assertEquals(self::STATUS_OK, $resp->getStatusCode());
        self::assertArrayHasKey('visits', $payload);
        self::assertArrayHasKey('data', $payload['visits']);
        self::assertCount($expectedVisitsAmount, $payload['visits']['data']);
    }

    public static function provideTags(): iterable
    {
        yield 'foo with admin API key' => ['valid_api_key', 'foo', [], 5];
        yield 'foo with admin API key and no bots' => ['valid_api_key', 'foo', ['excludeBots' => true], 4];
        yield 'bar with admin API key' => ['valid_api_key', 'bar', [], 2];
        yield 'bar with admin API key and no bots' => ['valid_api_key', 'bar', ['excludeBots' => true], 1];
        yield 'baz with admin API key' => ['valid_api_key', 'baz', [], 0];
        yield 'foo with author API key' => ['author_api_key', 'foo', [], 5];
        yield 'foo with author API key and no bots' => ['author_api_key', 'foo', ['excludeBots' => true], 4];
        yield 'bar with author API key' => ['author_api_key', 'bar', [], 2];
        yield 'bar with author API key and no bots' => ['author_api_key', 'bar', ['excludeBots' => true], 1];
        yield 'foo with domain API key' => ['domain_api_key', 'foo', [], 0];
        yield 'foo with specific domain' => ['valid_api_key', 'foo', ['domain' => 'example.com'], 0];
        yield 'foo with default domain' => ['valid_api_key', 'foo', ['domain' => 'DEFAULT'], 5];
    }

    #[Test, DataProvider('provideApiKeysAndTags')]
    public function notFoundErrorIsReturnedForInvalidTags(string $apiKey, string $tag): void
    {
        $resp = $this->callApiWithKey(self::METHOD_GET, sprintf('/tags/%s/visits', $tag), [], $apiKey);
        $payload = $this->getJsonResponsePayload($resp);

        self::assertEquals(self::STATUS_NOT_FOUND, $resp->getStatusCode());
        self::assertEquals(self::STATUS_NOT_FOUND, $payload['status']);
        self::assertEquals('https://shlink.io/api/error/tag-not-found', $payload['type']);
        self::assertEquals(sprintf('Tag with name "%s" could not be found', $tag), $payload['detail']);
        self::assertEquals('Tag not found', $payload['title']);
    }

    public static function provideApiKeysAndTags(): iterable
    {
        yield 'admin API key with invalid tag' => ['valid_api_key', 'invalid_tag'];
        yield 'domain API key with valid tag not used' => ['domain_api_key', 'bar'];
        yield 'author API key with valid tag not used' => ['author_api_key', 'baz'];
    }
}
