<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Middleware;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

use function sprintf;

class AuthenticationTest extends ApiTestCase
{
    #[Test, DataProvider('provideApiVersions')]
    public function authorizationErrorIsReturnedIfNoApiKeyIsSent(string $version, string $expectedType): void
    {
        $expectedDetail = 'Expected one of the following authentication headers, ["X-Api-Key"], but none were provided';

        $resp = $this->callApi(self::METHOD_GET, sprintf('/rest/v%s/short-urls', $version));
        $payload = $this->getJsonResponsePayload($resp);

        self::assertEquals(self::STATUS_UNAUTHORIZED, $resp->getStatusCode());
        self::assertEquals(self::STATUS_UNAUTHORIZED, $payload['status']);
        self::assertEquals($expectedType, $payload['type']);
        self::assertEquals($expectedDetail, $payload['detail']);
        self::assertEquals('Invalid authorization', $payload['title']);
    }

    public static function provideApiVersions(): iterable
    {
        yield 'version 1' => ['1', 'https://shlink.io/api/error/missing-authentication'];
        yield 'version 2' => ['2', 'https://shlink.io/api/error/missing-authentication'];
        yield 'version 3' => ['3', 'https://shlink.io/api/error/missing-authentication'];
    }

    #[Test, DataProvider('provideInvalidApiKeys')]
    public function apiKeyErrorIsReturnedWhenProvidedApiKeyIsInvalid(
        string $apiKey,
        string $version,
        string $expectedType,
    ): void {
        $expectedDetail = 'Provided API key does not exist or is invalid.';

        $resp = $this->callApi(self::METHOD_GET, sprintf('/rest/v%s/short-urls', $version), [
            'headers' => [
                'X-Api-Key' => $apiKey,
            ],
        ]);
        $payload = $this->getJsonResponsePayload($resp);

        self::assertEquals(self::STATUS_UNAUTHORIZED, $resp->getStatusCode());
        self::assertEquals(self::STATUS_UNAUTHORIZED, $payload['status']);
        self::assertEquals($expectedType, $payload['type']);
        self::assertEquals($expectedDetail, $payload['detail']);
        self::assertEquals('Invalid API key', $payload['title']);
    }

    public static function provideInvalidApiKeys(): iterable
    {
        yield 'key which does not exist' => ['invalid', '2', 'https://shlink.io/api/error/invalid-api-key'];
        yield 'key which is expired' => ['expired_api_key', '2', 'https://shlink.io/api/error/invalid-api-key'];
        yield 'key which is disabled' => ['disabled_api_key', '2', 'https://shlink.io/api/error/invalid-api-key'];
        yield 'version 3' => ['disabled_api_key', '3', 'https://shlink.io/api/error/invalid-api-key'];
    }
}
