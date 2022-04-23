<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use GuzzleHttp\RequestOptions;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

use function sprintf;

class DomainVisitsTest extends ApiTestCase
{
    /**
     * @test
     * @dataProvider provideDomains
     */
    public function expectedVisitsAreReturned(
        string $apiKey,
        string $domain,
        bool $excludeBots,
        int $expectedVisitsAmount,
    ): void {
        $resp = $this->callApiWithKey(self::METHOD_GET, sprintf('/domains/%s/visits', $domain), [
            RequestOptions::QUERY => $excludeBots ? ['excludeBots' => true] : [],
        ], $apiKey);
        $payload = $this->getJsonResponsePayload($resp);

        self::assertEquals(self::STATUS_OK, $resp->getStatusCode());
        self::assertArrayHasKey('visits', $payload);
        self::assertArrayHasKey('data', $payload['visits']);
        self::assertCount($expectedVisitsAmount, $payload['visits']['data']);
    }

    public function provideDomains(): iterable
    {
        yield 'example.com with admin API key' => ['valid_api_key', 'example.com', false, 0];
        yield 'DEFAULT with admin API key' => ['valid_api_key', 'DEFAULT', false, 7];
        yield 'DEFAULT with admin API key and no bots' => ['valid_api_key', 'DEFAULT', true, 6];
        yield 'DEFAULT with domain API key' => ['domain_api_key', 'DEFAULT', false, 0];
        yield 'DEFAULT with author API key' => ['author_api_key', 'DEFAULT', false, 5];
        yield 'DEFAULT with author API key and no bots' => ['author_api_key', 'DEFAULT', true, 4];
    }

    /**
     * @test
     * @dataProvider provideApiKeysAndTags
     */
    public function notFoundErrorIsReturnedForInvalidTags(string $apiKey, string $domain): void
    {
        $resp = $this->callApiWithKey(self::METHOD_GET, sprintf('/domains/%s/visits', $domain), [], $apiKey);
        $payload = $this->getJsonResponsePayload($resp);

        self::assertEquals(self::STATUS_NOT_FOUND, $resp->getStatusCode());
        self::assertEquals(self::STATUS_NOT_FOUND, $payload['status']);
        self::assertEquals('DOMAIN_NOT_FOUND', $payload['type']);
        self::assertEquals(sprintf('Domain with authority "%s" could not be found', $domain), $payload['detail']);
        self::assertEquals('Domain not found', $payload['title']);
        self::assertEquals($domain, $payload['authority']);
    }

    public function provideApiKeysAndTags(): iterable
    {
        yield 'admin API key with invalid domain' => ['valid_api_key', 'invalid_domain.com'];
        yield 'domain API key with not-owned valid domain' => ['domain_api_key', 'this_domain_is_detached.com'];
        yield 'author API key with valid domain not used in URLs' => ['author_api_key', 'this_domain_is_detached.com'];
    }
}
