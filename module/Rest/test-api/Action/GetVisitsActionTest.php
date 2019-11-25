<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use Shlinkio\Shlink\Rest\Util\RestUtils;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

class GetVisitsActionTest extends ApiTestCase
{
    /** @test */
    public function tryingToGetVisitsForInvalidUrlReturnsNotFoundError(): void
    {
        $resp = $this->callApiWithKey(self::METHOD_GET, '/short-urls/invalid/visits');
        ['error' => $error] = $this->getJsonResponsePayload($resp);

        $this->assertEquals(self::STATUS_NOT_FOUND, $resp->getStatusCode());
        $this->assertEquals(RestUtils::INVALID_SHORTCODE_ERROR, $error);
    }
}
