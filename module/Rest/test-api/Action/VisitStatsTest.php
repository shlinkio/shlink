<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

class VisitStatsTest extends ApiTestCase
{
    #[Test, DataProvider('provideApiKeysAndResults')]
    public function expectedStatsAreReturned(string $apiKey, array $expectedPayload): void
    {
        $resp = $this->callApiWithKey(self::METHOD_GET, '/visits', apiKey: $apiKey);
        $payload = $this->getJsonResponsePayload($resp);

        self::assertEquals(['visits' => $expectedPayload], $payload);
    }

    public static function provideApiKeysAndResults(): iterable
    {
        yield 'valid API key' => ['valid_api_key', [
            'nonOrphanVisits' => [
                'total' => 7,
                'nonBots' => 6,
                'bots' => 1,
            ],
            'orphanVisits' => [
                'total' => 3,
                'nonBots' => 2,
                'bots' => 1,
            ],
            'visitsCount' => 7,
            'orphanVisitsCount' => 3,
        ]];
        yield 'domain-only API key' => ['domain_api_key', [
            'nonOrphanVisits' => [
                'total' => 0,
                'nonBots' => 0,
                'bots' => 0,
            ],
            'orphanVisits' => [
                'total' => 3,
                'nonBots' => 2,
                'bots' => 1,
            ],
            'visitsCount' => 0,
            'orphanVisitsCount' => 3,
        ]];
        yield 'author API key' => ['author_api_key', [
            'nonOrphanVisits' => [
                'total' => 5,
                'nonBots' => 4,
                'bots' => 1,
            ],
            'orphanVisits' => [
                'total' => 3,
                'nonBots' => 2,
                'bots' => 1,
            ],
            'visitsCount' => 5,
            'orphanVisitsCount' => 3,
        ]];
    }
}
