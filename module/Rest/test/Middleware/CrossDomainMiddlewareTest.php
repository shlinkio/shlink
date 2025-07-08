<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Middleware;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\Config\Options\CorsOptions;
use Shlinkio\Shlink\Rest\Middleware\CrossDomainMiddleware;

class CrossDomainMiddlewareTest extends TestCase
{
    private MockObject & RequestHandlerInterface $handler;

    protected function setUp(): void
    {
        $this->handler = $this->createMock(RequestHandlerInterface::class);
    }

    #[Test]
    public function nonCrossDomainRequestsAreNotAffected(): void
    {
        $originalResponse = (new Response())->withStatus(404);
        $this->handler->expects($this->once())->method('handle')->willReturn($originalResponse);

        $response = $this->middleware()->process(new ServerRequest(), $this->handler);
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

        $response = $this->middleware()->process((new ServerRequest())->withHeader('Origin', 'local'), $this->handler);
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

        $response = $this->middleware()->process($request, $this->handler);
        self::assertNotSame($originalResponse, $response);

        $headers = $response->getHeaders();

        self::assertEquals('*', $response->getHeaderLine('Access-Control-Allow-Origin'));
        self::assertArrayHasKey('Access-Control-Allow-Methods', $headers);
        self::assertEquals('1000', $response->getHeaderLine('Access-Control-Max-Age'));
        self::assertEquals('foo, bar, baz', $response->getHeaderLine('Access-Control-Allow-Headers'));
        self::assertEquals(204, $response->getStatusCode());
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

        $response = $this->middleware()->process($request, $this->handler);

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

        $response = $this->middleware()->process($request, $this->handler);

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

    #[Test]
    #[TestWith([true])]
    #[TestWith([false])]
    public function credentialsAreAllowedIfConfiguredSo(bool $allowCredentials): void
    {
        $originalResponse = new Response();
        $request = (new ServerRequest())
            ->withMethod('OPTIONS')
            ->withHeader('Origin', 'local');
        $this->handler->method('handle')->willReturn($originalResponse);

        $response = $this->middleware(allowCredentials: $allowCredentials)->process($request, $this->handler);
        $headers = $response->getHeaders();

        if ($allowCredentials) {
            self::assertArrayHasKey('Access-Control-Allow-Credentials', $headers);
        } else {
            self::assertArrayNotHasKey('Access-Control-Allow-Credentials', $headers);
        }
    }

    private function middleware(bool $allowCredentials = false): CrossDomainMiddleware
    {
        return new CrossDomainMiddleware(new CorsOptions(allowCredentials: $allowCredentials, maxAge: 1000));
    }
}
