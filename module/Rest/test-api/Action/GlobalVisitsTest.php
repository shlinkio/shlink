<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

class GlobalVisitsTest extends ApiTestCase
{
    #[Test, DataProvider('provideApiKeys')]
    public function returnsExpectedVisitsStats(string $apiKey, int $expectedVisits, int $expectedOrphanVisits): void
    {
        $resp = $this->callApiWithKey(self::METHOD_GET, '/visits', [], $apiKey);
        $payload = $this->getJsonResponsePayload($resp);

        self::assertArrayHasKey('visits', $payload);
        self::assertEquals($expectedVisits, $payload['visits']['nonOrphanVisits']['total']);
        self::assertEquals($expectedOrphanVisits, $payload['visits']['orphanVisits']['total']);
    }

    public static function provideApiKeys(): iterable
    {
        yield 'admin API key' => ['valid_api_key', 7, 3];
        yield 'domain API key' => ['domain_api_key', 0, 3];
        yield 'author API key' => ['author_api_key', 5, 3];
        yield 'no orphans API key' => ['no_orphans_api_key', 7, 0];
    }
}
