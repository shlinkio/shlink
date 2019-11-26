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
        $resp = $this->callApi(self::METHOD_GET, '/short-codes');
        ['error' => $error, 'message' => $message] = $this->getJsonResponsePayload($resp);

        $this->assertEquals(self::STATUS_UNAUTHORIZED, $resp->getStatusCode());
        $this->assertEquals('INVALID_AUTHORIZATION', $error);
        $this->assertEquals(
            sprintf(
                'Expected one of the following authentication headers, but none were provided, ["%s"]',
                implode('", "', RequestToHttpAuthPlugin::SUPPORTED_AUTH_HEADERS)
            ),
            $message
        );
    }

    /**
     * @test
     * @dataProvider provideInvalidApiKeys
     */
    public function apiKeyErrorIsReturnedWhenProvidedApiKeyIsInvalid(string $apiKey): void
    {
        $resp = $this->callApi(self::METHOD_GET, '/short-codes', [
            'headers' => [
                Plugin\ApiKeyHeaderPlugin::HEADER_NAME => $apiKey,
            ],
        ]);
        ['error' => $error, 'message' => $message] = $this->getJsonResponsePayload($resp);

        $this->assertEquals(self::STATUS_UNAUTHORIZED, $resp->getStatusCode());
        $this->assertEquals('INVALID_API_KEY', $error);
        $this->assertEquals('Provided API key does not exist or is invalid.', $message);
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
        string $expectedMessage,
        string $expectedError
    ): void {
        $resp = $this->callApi(self::METHOD_GET, '/short-codes', [
            'headers' => [
                Plugin\AuthorizationHeaderPlugin::HEADER_NAME => $authValue,
            ],
        ]);
        ['error' => $error, 'message' => $message] = $this->getJsonResponsePayload($resp);

        $this->assertEquals(self::STATUS_UNAUTHORIZED, $resp->getStatusCode());
        $this->assertEquals($expectedError, $error);
        $this->assertEquals($expectedMessage, $message);
    }

    public function provideInvalidAuthorizations(): iterable
    {
        yield 'no type' => [
            'invalid',
            'You need to provide the Bearer type in the Authorization header.',
            'INVALID_AUTHORIZATION',
        ];
        yield 'invalid type' => [
            'Basic invalid',
            'Provided authorization type Basic is not supported. Use Bearer instead.',
            'INVALID_AUTHORIZATION',
        ];
        yield 'invalid JWT' => [
            'Bearer invalid',
            'Missing or invalid auth token provided. Perform a new authentication request and send provided '
            . 'token on every new request on the Authorization header',
            'INVALID_AUTH_TOKEN',
        ];
    }
}
