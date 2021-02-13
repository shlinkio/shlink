<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

class GlobalVisitsTest extends ApiTestCase
{
    /**
     * @test
     * @dataProvider provideApiKeys
     */
    public function returnsExpectedVisitsStats(string $apiKey, int $expectedVisits): void
    {
        $resp = $this->callApiWithKey(self::METHOD_GET, '/visits', [], $apiKey);
        $payload = $this->getJsonResponsePayload($resp);

        self::assertArrayHasKey('visits', $payload);
        self::assertArrayHasKey('visitsCount', $payload['visits']);
        self::assertArrayHasKey('orphanVisitsCount', $payload['visits']);
        self::assertEquals($expectedVisits, $payload['visits']['visitsCount']);
        self::assertEquals(3, $payload['visits']['orphanVisitsCount']);
    }

    public function provideApiKeys(): iterable
    {
        yield 'admin API key' => ['valid_api_key', 7];
        yield 'domain API key' => ['domain_api_key', 0];
        yield 'author API key' => ['author_api_key', 5];
    }
}
