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

        $this->assertArrayHasKey('visits', $payload);
        $this->assertArrayHasKey('visitsCount', $payload['visits']);
        $this->assertEquals(7, $payload['visits']['visitsCount']);
    }
}
