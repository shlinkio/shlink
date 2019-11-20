<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use GuzzleHttp\RequestOptions;
use Shlinkio\Shlink\Rest\Util\RestUtils;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

class EditShortUrlActionTest extends ApiTestCase
{
    /** @test */
    public function tryingToEditInvalidUrlReturnsNotFoundError(): void
    {
        $resp = $this->callApiWithKey(self::METHOD_PATCH, '/short-urls/invalid', [RequestOptions::JSON => []]);
        ['error' => $error] = $this->getJsonResponsePayload($resp);

        $this->assertEquals(self::STATUS_NOT_FOUND, $resp->getStatusCode());
        $this->assertEquals(RestUtils::INVALID_SHORTCODE_ERROR, $error);
    }

    /** @test */
    public function providingInvalidDataReturnsBadRequest(): void
    {
        $resp = $this->callApiWithKey(self::METHOD_PATCH, '/short-urls/invalid', [RequestOptions::JSON => [
            'maxVisits' => 'not_a_number',
        ]]);
        ['error' => $error] = $this->getJsonResponsePayload($resp);

        $this->assertEquals(self::STATUS_BAD_REQUEST, $resp->getStatusCode());
        $this->assertEquals(RestUtils::INVALID_ARGUMENT_ERROR, $error);
    }
}
