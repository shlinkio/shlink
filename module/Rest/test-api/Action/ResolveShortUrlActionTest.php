<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

class ResolveShortUrlActionTest extends ApiTestCase
{
    /** @test */
    public function tryingToResolveInvalidUrlReturnsNotFoundError(): void
    {
        $expectedDetail = 'No URL found with short code "invalid"';

        $resp = $this->callApiWithKey(self::METHOD_GET, '/short-urls/invalid');
        $payload = $this->getJsonResponsePayload($resp);

        $this->assertEquals(self::STATUS_NOT_FOUND, $resp->getStatusCode());
        $this->assertEquals(self::STATUS_NOT_FOUND, $payload['status']);
        $this->assertEquals('INVALID_SHORTCODE', $payload['type']);
        $this->assertEquals('INVALID_SHORTCODE', $payload['error']); // Deprecated
        $this->assertEquals($expectedDetail, $payload['detail']);
        $this->assertEquals($expectedDetail, $payload['message']); // Deprecated
        $this->assertEquals('Short URL not found', $payload['title']);
        $this->assertEquals('invalid', $payload['shortCode']);
    }
}
