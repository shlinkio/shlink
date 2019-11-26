<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

class ResolveShortUrlActionTest extends ApiTestCase
{
    /** @test */
    public function tryingToResolveInvalidUrlReturnsNotFoundError(): void
    {
        $resp = $this->callApiWithKey(self::METHOD_GET, '/short-urls/invalid');
        ['error' => $error] = $this->getJsonResponsePayload($resp);

        $this->assertEquals(self::STATUS_NOT_FOUND, $resp->getStatusCode());
        $this->assertEquals('INVALID_SHORTCODE', $error);
    }
}
