<?php
declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Middleware;

use GuzzleHttp\Exception\ClientException;
use ShlinkioTest\Shlink\Common\ApiTest\ApiTestCase;

class AuthenticationTest extends ApiTestCase
{
    /**
     * @test
     */
    public function unauthorizedIsReturnedIfNoAuthenticationIsSent()
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionCode(self::STATUS_UNAUTHORIZED);

        $this->callApi(self::METHOD_GET, '/rest/v1/short-codes');
    }
}
