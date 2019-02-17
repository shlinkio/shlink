<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Middleware;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Rest\Middleware\CrossDomainMiddleware;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;

class CrossDomainMiddlewareTest extends TestCase
{
    /** @var CrossDomainMiddleware */
    private $middleware;
    /** @var ObjectProphecy */
    private $delegate;

    public function setUp(): void
    {
        $this->middleware = new CrossDomainMiddleware();
        $this->delegate = $this->prophesize(RequestHandlerInterface::class);
    }

    /** @test */
    public function nonCrossDomainRequestsAreNotAffected()
    {
        $originalResponse = new Response();
        $this->delegate->handle(Argument::any())->willReturn($originalResponse)->shouldBeCalledOnce();

        $response = $this->middleware->process(new ServerRequest(), $this->delegate->reveal());
        $this->assertSame($originalResponse, $response);

        $headers = $response->getHeaders();
        $this->assertArrayNotHasKey('Access-Control-Allow-Origin', $headers);
        $this->assertArrayNotHasKey('Access-Control-Allow-Headers', $headers);
    }

    /** @test */
    public function anyRequestIncludesTheAllowAccessHeader()
    {
        $originalResponse = new Response();
        $this->delegate->handle(Argument::any())->willReturn($originalResponse)->shouldBeCalledOnce();

        $response = $this->middleware->process(
            (new ServerRequest())->withHeader('Origin', 'local'),
            $this->delegate->reveal()
        );
        $this->assertNotSame($originalResponse, $response);

        $headers = $response->getHeaders();
        $this->assertArrayHasKey('Access-Control-Allow-Origin', $headers);
        $this->assertArrayNotHasKey('Access-Control-Allow-Headers', $headers);
    }

    /** @test */
    public function optionsRequestIncludesMoreHeaders()
    {
        $originalResponse = new Response();
        $request = (new ServerRequest())->withMethod('OPTIONS')->withHeader('Origin', 'local');
        $this->delegate->handle(Argument::any())->willReturn($originalResponse)->shouldBeCalledOnce();

        $response = $this->middleware->process($request, $this->delegate->reveal());
        $this->assertNotSame($originalResponse, $response);

        $headers = $response->getHeaders();
        $this->assertArrayHasKey('Access-Control-Allow-Origin', $headers);
        $this->assertArrayHasKey('Access-Control-Allow-Headers', $headers);
    }
}
