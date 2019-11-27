<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Middleware;

use Shlinkio\Shlink\Rest\Authentication\Plugin;
use Shlinkio\Shlink\Rest\Authentication\RequestToHttpAuthPlugin;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

use function implode;
use function sprintf;

class AuthenticationTest extends ApiTestCase
{
    /** @test */
    public function authorizationErrorIsReturnedIfNoApiKeyIsSent(): void
    {
        $expectedDetail = sprintf(
            'Expected one of the following authentication headers, but none were provided, ["%s"]',
            implode('", "', RequestToHttpAuthPlugin::SUPPORTED_AUTH_HEADERS)
        );

        $resp = $this->callApi(self::METHOD_GET, '/short-codes');
        $payload = $this->getJsonResponsePayload($resp);

        $this->assertEquals(self::STATUS_UNAUTHORIZED, $resp->getStatusCode());
        $this->assertEquals(self::STATUS_UNAUTHORIZED, $payload['status']);
        $this->assertEquals('INVALID_AUTHORIZATION', $payload['type']);
        $this->assertEquals('INVALID_AUTHORIZATION', $payload['error']); // Deprecated
        $this->assertEquals($expectedDetail, $payload['detail']);
        $this->assertEquals($expectedDetail, $payload['message']); // Deprecated
        $this->assertEquals('Invalid authorization', $payload['title']);
    }

    /**
     * @test
     * @dataProvider provideInvalidApiKeys
     */
    public function apiKeyErrorIsReturnedWhenProvidedApiKeyIsInvalid(string $apiKey): void
    {
        $expectedDetail = 'Provided API key does not exist or is invalid.';

        $resp = $this->callApi(self::METHOD_GET, '/short-codes', [
            'headers' => [
                Plugin\ApiKeyHeaderPlugin::HEADER_NAME => $apiKey,
            ],
        ]);
        $payload = $this->getJsonResponsePayload($resp);

        $this->assertEquals(self::STATUS_UNAUTHORIZED, $resp->getStatusCode());
        $this->assertEquals(self::STATUS_UNAUTHORIZED, $payload['status']);
        $this->assertEquals('INVALID_API_KEY', $payload['type']);
        $this->assertEquals('INVALID_API_KEY', $payload['error']); // Deprecated
        $this->assertEquals($expectedDetail, $payload['detail']);
        $this->assertEquals($expectedDetail, $payload['message']); // Deprecated
        $this->assertEquals('Invalid API key', $payload['title']);
    }

    public function provideInvalidApiKeys(): iterable
    {
        yield 'key which does not exist' => ['invalid'];
        yield 'key which is expired' => ['expired_api_key'];
        yield 'key which is disabled' => ['disabled_api_key'];
    }

    /**
     * @test
     * @dataProvider provideInvalidAuthorizations
     */
    public function authorizationErrorIsReturnedIfInvalidDataIsProvided(
        string $authValue,
        string $expectedDetail,
        string $expectedType,
        string $expectedTitle
    ): void {
        $resp = $this->callApi(self::METHOD_GET, '/short-codes', [
            'headers' => [
                Plugin\AuthorizationHeaderPlugin::HEADER_NAME => $authValue,
            ],
        ]);
        $payload = $this->getJsonResponsePayload($resp);

        $this->assertEquals(self::STATUS_UNAUTHORIZED, $resp->getStatusCode());
        $this->assertEquals(self::STATUS_UNAUTHORIZED, $payload['status']);
        $this->assertEquals($expectedType, $payload['type']);
        $this->assertEquals($expectedType, $payload['error']); // Deprecated
        $this->assertEquals($expectedDetail, $payload['detail']);
        $this->assertEquals($expectedDetail, $payload['message']); // Deprecated
        $this->assertEquals($expectedTitle, $payload['title']);
    }

    public function provideInvalidAuthorizations(): iterable
    {
        yield 'no type' => [
            'invalid',
            'You need to provide the Bearer type in the Authorization header.',
            'INVALID_AUTHORIZATION',
            'Invalid authorization',
        ];
        yield 'invalid type' => [
            'Basic invalid',
            'Provided authorization type Basic is not supported. Use Bearer instead.',
            'INVALID_AUTHORIZATION',
            'Invalid authorization',
        ];
        yield 'invalid JWT' => [
            'Bearer invalid',
            'Missing or invalid auth token provided. Perform a new authentication request and send provided '
            . 'token on every new request on the Authorization header',
            'INVALID_AUTH_TOKEN',
            'Invalid auth token',
        ];
    }
}
