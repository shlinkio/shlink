<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Middleware;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\Config\EnvVars;
use Shlinkio\Shlink\Rest\Middleware\CrossDomainMiddleware;

use function putenv;

class CrossDomainMiddlewareTest extends TestCase
{
    private CrossDomainMiddleware $middleware;
    private MockObject & RequestHandlerInterface $handler;

    protected function setUp(): void
    {
        $this->middleware = new CrossDomainMiddleware();
        $this->handler = $this->createMock(RequestHandlerInterface::class);
    }

    #[Test]
    public function nonCrossDomainRequestsAreNotAffected(): void
    {
        $originalResponse = (new Response())->withStatus(404);
        $this->handler->expects($this->once())->method('handle')->willReturn($originalResponse);

        $response = $this->middleware->process(new ServerRequest(), $this->handler);
        $headers = $response->getHeaders();

        self::assertSame($originalResponse, $response);
        self::assertEquals(404, $response->getStatusCode());
        self::assertArrayNotHasKey('Access-Control-Allow-Origin', $headers);
        self::assertArrayNotHasKey('Access-Control-Allow-Methods', $headers);
        self::assertArrayNotHasKey('Access-Control-Max-Age', $headers);
        self::assertArrayNotHasKey('Access-Control-Allow-Headers', $headers);
    }

    #[Test]
    public function anyRequestIncludesTheAllowAccessHeader(): void
    {
        $originalResponse = new Response();
        $this->handler->expects($this->once())->method('handle')->willReturn($originalResponse);

        $response = $this->middleware->process((new ServerRequest())->withHeader('Origin', 'local'), $this->handler);
        self::assertNotSame($originalResponse, $response);

        $headers = $response->getHeaders();

        self::assertEquals('*', $response->getHeaderLine('Access-Control-Allow-Origin'));
        self::assertArrayNotHasKey('Access-Control-Allow-Methods', $headers);
        self::assertArrayNotHasKey('Access-Control-Max-Age', $headers);
        self::assertArrayNotHasKey('Access-Control-Allow-Headers', $headers);
    }

    #[Test]
    public function optionsRequestIncludesMoreHeaders(): void
    {
        $originalResponse = new Response();
        $request = (new ServerRequest())
            ->withMethod('OPTIONS')
            ->withHeader('Origin', 'local')
            ->withHeader('Access-Control-Request-Headers', 'foo, bar, baz');
        $this->handler->expects($this->once())->method('handle')->willReturn($originalResponse);

        $response = $this->middleware->process($request, $this->handler);
        self::assertNotSame($originalResponse, $response);

        $headers = $response->getHeaders();

        self::assertEquals('*', $response->getHeaderLine('Access-Control-Allow-Origin'));
        self::assertArrayHasKey('Access-Control-Allow-Methods', $headers);
        self::assertEquals('3600', $response->getHeaderLine('Access-Control-Max-Age'));
        self::assertEquals('foo, bar, baz', $response->getHeaderLine('Access-Control-Allow-Headers'));
        self::assertEquals(204, $response->getStatusCode());
    }

    #[Test]
    public function optionsRequestIncludesCorsHeadersFromEnvironment(): void
    {
        putenv(EnvVars::CORS_ALLOW_CREDENTIALS->value . '=true');
        putenv(EnvVars::CORS_ALLOW_ORIGIN->value . '=https://example.com');
        putenv(EnvVars::CORS_ALLOW_HEADERS->value . '=Foo, Bar');
        putenv(EnvVars::CORS_MAX_AGE->value . '=1000');

        try {
            $this->middleware = new CrossDomainMiddleware();

            $originalResponse = new Response();
            $request = (new ServerRequest())
                ->withMethod('OPTIONS')
                ->withHeader('Origin', 'local')
                ->withHeader('Access-Control-Request-Headers', 'foo, bar, baz');
            $this->handler->expects($this->once())->method('handle')->willReturn($originalResponse);

            $response = $this->middleware->process($request, $this->handler);
            self::assertNotSame($originalResponse, $response);

            $headers = $response->getHeaders();

            self::assertEquals('https://example.com', $response->getHeaderLine('Access-Control-Allow-Origin'));
            self::assertArrayHasKey('Access-Control-Allow-Methods', $headers);
            self::assertEquals('1000', $response->getHeaderLine('Access-Control-Max-Age'));
            self::assertEquals('true', $response->getHeaderLine('Access-Control-Allow-Credentials'));
            self::assertEquals('Foo, Bar', $response->getHeaderLine('Access-Control-Allow-Headers'));
            self::assertEquals(204, $response->getStatusCode());
        } finally {
            putenv(EnvVars::CORS_ALLOW_CREDENTIALS->value);
            putenv(EnvVars::CORS_ALLOW_ORIGIN->value);
            putenv(EnvVars::CORS_ALLOW_HEADERS->value);
            putenv(EnvVars::CORS_MAX_AGE->value);

            $this->middleware = new CrossDomainMiddleware();
        }
    }


    #[Test, DataProvider('provideRouteResults')]
    public function optionsRequestParsesRouteMatchToDetermineAllowedMethods(
        string|null $allowHeader,
        string $expectedAllowedMethods,
    ): void {
        $originalResponse = new Response();
        if ($allowHeader !== null) {
            $originalResponse = $originalResponse->withHeader('Allow', $allowHeader);
        }
        $request = (new ServerRequest())->withHeader('Origin', 'local')
                                        ->withMethod('OPTIONS');
        $this->handler->expects($this->once())->method('handle')->willReturn($originalResponse);

        $response = $this->middleware->process($request, $this->handler);

        self::assertEquals($response->getHeaderLine('Access-Control-Allow-Methods'), $expectedAllowedMethods);
        self::assertEquals(204, $response->getStatusCode());
    }

    public static function provideRouteResults(): iterable
    {
        yield 'no allow header in response' => [null, 'GET,POST,PUT,PATCH,DELETE'];
        yield 'allow header in response' => ['POST,GET', 'POST,GET'];
        yield 'also allow header in response' => ['DELETE,PATCH,PUT', 'DELETE,PATCH,PUT'];
    }

    #[Test, DataProvider('provideMethods')]
    public function expectedStatusCodeIsReturnDependingOnRequestMethod(
        string $method,
        int $status,
        int $expectedStatus,
    ): void {
        $originalResponse = (new Response())->withStatus($status);
        $request = (new ServerRequest())->withMethod($method)
                                        ->withHeader('Origin', 'local');
        $this->handler->expects($this->once())->method('handle')->willReturn($originalResponse);

        $response = $this->middleware->process($request, $this->handler);

        self::assertEquals($expectedStatus, $response->getStatusCode());
    }

    public static function provideMethods(): iterable
    {
        yield 'POST 200' => ['POST', 200, 200];
        yield 'POST 400' => ['POST', 400, 400];
        yield 'POST 500' => ['POST', 500, 500];
        yield 'GET 200' => ['GET', 200, 200];
        yield 'GET 400' => ['GET', 400, 400];
        yield 'GET 500' => ['GET', 500, 500];
        yield 'PATCH 200' => ['PATCH', 200, 200];
        yield 'PATCH 400' => ['PATCH', 400, 400];
        yield 'PATCH 500' => ['PATCH', 500, 500];
        yield 'DELETE 200' => ['DELETE', 200, 200];
        yield 'DELETE 400' => ['DELETE', 400, 400];
        yield 'DELETE 500' => ['DELETE', 500, 500];
        yield 'OPTIONS 200' => ['OPTIONS', 200, 204];
        yield 'OPTIONS 400' => ['OPTIONS', 400, 204];
        yield 'OPTIONS 500' => ['OPTIONS', 500, 204];
    }
}
