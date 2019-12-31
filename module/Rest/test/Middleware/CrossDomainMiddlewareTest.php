<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Middleware;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Rest\Authentication;
use Shlinkio\Shlink\Rest\Middleware\CrossDomainMiddleware;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\Expressive\Router\Route;
use Zend\Expressive\Router\RouteResult;

use function Zend\Stratigility\middleware;

class CrossDomainMiddlewareTest extends TestCase
{
    private CrossDomainMiddleware $middleware;
    private ObjectProphecy $handler;

    public function setUp(): void
    {
        $this->middleware = new CrossDomainMiddleware();
        $this->handler = $this->prophesize(RequestHandlerInterface::class);
    }

    /** @test */
    public function nonCrossDomainRequestsAreNotAffected(): void
    {
        $originalResponse = new Response();
        $this->handler->handle(Argument::any())->willReturn($originalResponse)->shouldBeCalledOnce();

        $response = $this->middleware->process(new ServerRequest(), $this->handler->reveal());
        $this->assertSame($originalResponse, $response);

        $headers = $response->getHeaders();

        $this->assertArrayNotHasKey('Access-Control-Allow-Origin', $headers);
        $this->assertArrayNotHasKey('Access-Control-Expose-Headers', $headers);
        $this->assertArrayNotHasKey('Access-Control-Allow-Methods', $headers);
        $this->assertArrayNotHasKey('Access-Control-Max-Age', $headers);
        $this->assertArrayNotHasKey('Access-Control-Allow-Headers', $headers);
    }

    /** @test */
    public function anyRequestIncludesTheAllowAccessHeader(): void
    {
        $originalResponse = new Response();
        $this->handler->handle(Argument::any())->willReturn($originalResponse)->shouldBeCalledOnce();

        $response = $this->middleware->process(
            (new ServerRequest())->withHeader('Origin', 'local'),
            $this->handler->reveal()
        );
        $this->assertNotSame($originalResponse, $response);

        $headers = $response->getHeaders();

        $this->assertEquals('local', $response->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertEquals(
            Authentication\Plugin\ApiKeyHeaderPlugin::HEADER_NAME,
            $response->getHeaderLine('Access-Control-Expose-Headers')
        );
        $this->assertArrayNotHasKey('Access-Control-Allow-Methods', $headers);
        $this->assertArrayNotHasKey('Access-Control-Max-Age', $headers);
        $this->assertArrayNotHasKey('Access-Control-Allow-Headers', $headers);
    }

    /** @test */
    public function optionsRequestIncludesMoreHeaders(): void
    {
        $originalResponse = new Response();
        $request = (new ServerRequest())
            ->withMethod('OPTIONS')
            ->withHeader('Origin', 'local')
            ->withHeader('Access-Control-Request-Headers', 'foo, bar, baz');
        $this->handler->handle(Argument::any())->willReturn($originalResponse)->shouldBeCalledOnce();

        $response = $this->middleware->process($request, $this->handler->reveal());
        $this->assertNotSame($originalResponse, $response);

        $headers = $response->getHeaders();

        $this->assertEquals('local', $response->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertEquals(
            Authentication\Plugin\ApiKeyHeaderPlugin::HEADER_NAME,
            $response->getHeaderLine('Access-Control-Expose-Headers')
        );
        $this->assertArrayHasKey('Access-Control-Allow-Methods', $headers);
        $this->assertEquals('1000', $response->getHeaderLine('Access-Control-Max-Age'));
        $this->assertEquals('foo, bar, baz', $response->getHeaderLine('Access-Control-Allow-Headers'));
    }

    /**
     * @test
     * @dataProvider provideRouteResults
     */
    public function optionsRequestParsesRouteMatchToDetermineAllowedMethods(
        ?RouteResult $result,
        string $expectedAllowedMethods
    ): void {
        $originalResponse = new Response();
        $request = (new ServerRequest())->withAttribute(RouteResult::class, $result)
                                        ->withMethod('OPTIONS')
                                        ->withHeader('Origin', 'local');
        $this->handler->handle(Argument::any())->willReturn($originalResponse)->shouldBeCalledOnce();

        $response = $this->middleware->process($request, $this->handler->reveal());

        $this->assertEquals($response->getHeaderLine('Access-Control-Allow-Methods'), $expectedAllowedMethods);
    }

    public function provideRouteResults(): iterable
    {
        yield 'with no route result' => [null, 'GET,POST,PUT,PATCH,DELETE,OPTIONS'];
        yield 'with failed route result' => [RouteResult::fromRouteFailure(['POST', 'GET']), 'POST,GET'];
        yield 'with success route result' => [
            RouteResult::fromRoute(
                new Route('/', middleware(function () {
                }), ['DELETE', 'PATCH', 'PUT'])
            ),
            'DELETE,PATCH,PUT',
        ];
    }
}
