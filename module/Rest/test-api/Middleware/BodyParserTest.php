<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Middleware;

use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\Attributes\Test;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

class BodyParserTest extends ApiTestCase
{
    #[Test]
    public function returnsErrorWhenRequestBodyIsInvalidJson(): void
    {
        $resp = $this->callApiWithKey(self::METHOD_POST, '/short-urls', [
            RequestOptions::HEADERS => ['content-type' => 'application/json'],
            RequestOptions::BODY => '{"foo',
        ]);
        $payload = $this->getJsonResponsePayload($resp);

        self::assertEquals(400, $resp->getStatusCode());
        self::assertEquals(400, $payload['status']);
        self::assertEquals('Provided request does not contain a valid JSON body.', $payload['detail']);
        self::assertEquals('Malformed request body', $payload['title']);
        self::assertEquals('https://shlink.io/api/error/malformed-request-body', $payload['type']);
    }
}
