<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Middleware;

use GuzzleHttp\RequestOptions;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

class CorsTest extends ApiTestCase
{
    /** @test */
    public function responseDoesNotIncludeCorsHeadersWhenOriginIsNotSent(): void
    {
        $resp = $this->callApiWithKey(self::METHOD_GET, '/short-urls');

        self::assertEquals(200, $resp->getStatusCode());
        self::assertFalse($resp->hasHeader('Access-Control-Allow-Origin'));
        self::assertFalse($resp->hasHeader('Access-Control-Allow-Methods'));
        self::assertFalse($resp->hasHeader('Access-Control-Max-Age'));
        self::assertFalse($resp->hasHeader('Access-Control-Allow-Headers'));
    }

    /**
     * @test
     * @dataProvider provideOrigins
     */
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

    public function provideOrigins(): iterable
    {
        yield 'foo.com' => ['foo.com', '/short-urls', 200];
        yield 'bar.io' => ['bar.io', '/foo/bar', 404];
        yield 'baz.dev' => ['baz.dev', '/short-urls', 200];
    }

    /**
     * @test
     * @dataProvider providePreflightEndpoints
     */
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
        self::assertTrue($resp->hasHeader('Access-Control-Allow-Origin'));
        self::assertTrue($resp->hasHeader('Access-Control-Max-Age'));
        self::assertEquals($expectedAllowedMethods, $resp->getHeaderLine('Access-Control-Allow-Methods'));
        self::assertEquals($allowedHeaders, $resp->getHeaderLine('Access-Control-Allow-Headers'));
    }

    public function providePreflightEndpoints(): iterable
    {
        yield 'invalid route' => ['/foo/bar', 'GET,POST,PUT,PATCH,DELETE'];
        yield 'short URLs route' => ['/short-urls', 'GET,POST'];
        yield 'tags route' => ['/tags', 'GET,PUT,DELETE'];
        yield 'health route' => ['/health', 'GET'];
    }
}
