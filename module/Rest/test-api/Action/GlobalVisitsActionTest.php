<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

class GlobalVisitsActionTest extends ApiTestCase
{
    /** @test */
    public function returnsExpectedVisitsStats(): void
    {
        $resp = $this->callApiWithKey(self::METHOD_GET, '/visits');
        $payload = $this->getJsonResponsePayload($resp);

        self::assertArrayHasKey('visits', $payload);
        self::assertArrayHasKey('visitsCount', $payload['visits']);
        self::assertEquals(7, $payload['visits']['visitsCount']);
    }
}
