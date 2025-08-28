<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Middleware;

use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shlinkio\Shlink\Core\Config\EnvVars;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;
use ShlinkioApiTest\Shlink\Rest\Utils\WithEnvVars;

class CorsTest extends ApiTestCase
{
    #[Test]
    public function responseDoesNotIncludeCorsHeadersWhenOriginIsNotSent(): void
    {
        $resp = $this->callApiWithKey(self::METHOD_GET, '/short-urls');

        self::assertEquals(200, $resp->getStatusCode());
        self::assertFalse($resp->hasHeader('Access-Control-Allow-Origin'));
        self::assertFalse($resp->hasHeader('Access-Control-Allow-Methods'));
        self::assertFalse($resp->hasHeader('Access-Control-Max-Age'));
        self::assertFalse($resp->hasHeader('Access-Control-Allow-Headers'));
    }

    #[Test, DataProvider('provideOrigins')]
    public function responseIncludesCorsHeadersIfOriginIsSent(
        string $origin,
        string $endpoint,
        int $expectedStatusCode,
    ): void {
        $resp = $this->callApiWithKey(self::METHOD_GET, $endpoint, [
            RequestOptions::HEADERS => ['Origin' => $origin],
        ]);

        self::assertEquals($expectedStatusCode, $resp->getStatusCode());
        self::assertEquals('*', $resp->getHeaderLine('Access-Control-Allow-Origin'));
        self::assertFalse($resp->hasHeader('Access-Control-Allow-Methods'));
        self::assertFalse($resp->hasHeader('Access-Control-Max-Age'));
        self::assertFalse($resp->hasHeader('Access-Control-Allow-Headers'));
    }

    public static function provideOrigins(): iterable
    {
        yield 'foo.com' => ['foo.com', '/short-urls', 200];
        yield 'bar.io' => ['bar.io', '/foo/bar', 404];
        yield 'baz.dev' => ['baz.dev', '/short-urls', 200];
    }

    #[Test, DataProvider('providePreflightEndpoints')]
    public function preflightRequestsIncludeExtraCorsHeaders(string $endpoint, string $expectedAllowedMethods): void
    {
        $allowedHeaders = 'Authorization';
        $resp = $this->callApiWithKey(self::METHOD_OPTIONS, $endpoint, [
            RequestOptions::HEADERS => [
                'Origin' => 'foo.com',
                'Access-Control-Request-Headers' => $allowedHeaders,
            ],
        ]);

        self::assertEquals(204, $resp->getStatusCode());
        self::assertEquals('*', $resp->getHeaderLine('Access-Control-Allow-Origin'));
        self::assertEquals('3600', $resp->getHeaderLine('Access-Control-Max-Age'));
        self::assertEquals($expectedAllowedMethods, $resp->getHeaderLine('Access-Control-Allow-Methods'));
        self::assertEquals($allowedHeaders, $resp->getHeaderLine('Access-Control-Allow-Headers'));
    }

    public static function providePreflightEndpoints(): iterable
    {
        yield 'invalid route' => ['/foo/bar', 'GET,POST,PUT,PATCH,DELETE']; // TODO This won't work with multi-segment
        yield 'short URLs route' => ['/short-urls', 'GET,POST'];
        yield 'tags route' => ['/tags', 'GET,DELETE,PUT'];
        yield 'health route' => ['/health', 'GET'];
    }

    #[Test, WithEnvVars([
        EnvVars::CORS_MAX_AGE->value => 5000,
        EnvVars::CORS_ALLOW_CREDENTIALS->value => true,
        EnvVars::CORS_ALLOW_ORIGIN->value => 'example.com,localhost:8000',
    ])]
    public function parametersCanBeCustomized(): void
    {
        $resp = $this->callApiWithKey(self::METHOD_OPTIONS, '/short-urls', [
            RequestOptions::HEADERS => ['Origin' => 'example.com'],
        ]);

        self::assertEquals('5000', $resp->getHeaderLine('Access-Control-Max-Age'));
        self::assertEquals('example.com', $resp->getHeaderLine('Access-Control-Allow-Origin'));
        self::assertEquals('true', $resp->getHeaderLine('Access-Control-Allow-Credentials'));
    }
}
