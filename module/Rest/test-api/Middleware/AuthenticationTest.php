<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Middleware;

use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

class AuthenticationTest extends ApiTestCase
{
    /** @test */
    public function authorizationErrorIsReturnedIfNoApiKeyIsSent(): void
    {
        $expectedDetail = 'Expected one of the following authentication headers, ["X-Api-Key"], but none were provided';

        $resp = $this->callApi(self::METHOD_GET, '/short-urls');
        $payload = $this->getJsonResponsePayload($resp);

        self::assertEquals(self::STATUS_UNAUTHORIZED, $resp->getStatusCode());
        self::assertEquals(self::STATUS_UNAUTHORIZED, $payload['status']);
        self::assertEquals('INVALID_AUTHORIZATION', $payload['type']);
        self::assertEquals($expectedDetail, $payload['detail']);
        self::assertEquals('Invalid authorization', $payload['title']);
    }

    /**
     * @test
     * @dataProvider provideInvalidApiKeys
     */
    public function apiKeyErrorIsReturnedWhenProvidedApiKeyIsInvalid(string $apiKey): void
    {
        $expectedDetail = 'Provided API key does not exist or is invalid.';

        $resp = $this->callApi(self::METHOD_GET, '/short-urls', [
            'headers' => [
                'X-Api-Key' => $apiKey,
            ],
        ]);
        $payload = $this->getJsonResponsePayload($resp);

        self::assertEquals(self::STATUS_UNAUTHORIZED, $resp->getStatusCode());
        self::assertEquals(self::STATUS_UNAUTHORIZED, $payload['status']);
        self::assertEquals('INVALID_API_KEY', $payload['type']);
        self::assertEquals($expectedDetail, $payload['detail']);
        self::assertEquals('Invalid API key', $payload['title']);
    }

    public function provideInvalidApiKeys(): iterable
    {
        yield 'key which does not exist' => ['invalid'];
        yield 'key which is expired' => ['expired_api_key'];
        yield 'key which is disabled' => ['disabled_api_key'];
    }
}
