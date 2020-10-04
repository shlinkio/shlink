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
            'Expected one of the following authentication headers, ["%s"], but none were provided',
            implode('", "', RequestToHttpAuthPlugin::SUPPORTED_AUTH_HEADERS),
        );

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
                Plugin\ApiKeyHeaderPlugin::HEADER_NAME => $apiKey,
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
