<?php
namespace AcelayaTest\UrlShortener\Middleware;

use Acelaya\UrlShortener\Middleware\CliParamsMiddleware;
use PHPUnit_Framework_TestCase as TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Expressive\Router\RouteResult;

class CliParamsMiddlewareTest extends TestCase
{
    /**
     * @test
     */
    public function nonCliRequestsJustInvokeNextMiddleware()
    {
        $middleware = new CliParamsMiddleware([], 'non-cli');

        $invoked = false;
        $originalResponse = new Response();

        $response = $middleware->__invoke(
            ServerRequestFactory::fromGlobals(),
            $originalResponse,
            function ($req, $resp) use (&$invoked) {
                $invoked = true;
                return $resp;
            }
        );

        $this->assertSame($originalResponse, $response);
        $this->assertTrue($invoked);
    }

    /**
     * @test
     */
    public function nonSuccessRouteResultJustInvokesNextMiddleware()
    {
        $middleware = new CliParamsMiddleware([], 'cli');

        $invoked = false;
        $originalResponse = new Response();
        $routeResult = $this->prophesize(RouteResult::class);
        $routeResult->isSuccess()->willReturn(false)->shouldBeCalledTimes(1);

        $response = $middleware->__invoke(
            ServerRequestFactory::fromGlobals()->withAttribute(RouteResult::class, $routeResult->reveal()),
            $originalResponse,
            function ($req, $resp) use (&$invoked) {
                $invoked = true;
                return $resp;
            }
        );

        $this->assertSame($originalResponse, $response);
        $this->assertTrue($invoked);
    }

    /**
     * @test
     */
    public function properRouteWillInjectAttributeInResponse()
    {
        $expectedLongUrl = 'http://www.google.com';
        $middleware = new CliParamsMiddleware(['foo', 'bar', $expectedLongUrl], 'cli');

        $invoked = false;
        $originalResponse = new Response();
        $routeResult = $this->prophesize(RouteResult::class);
        $routeResult->isSuccess()->willReturn(true)->shouldBeCalledTimes(1);
        $routeResult->getMatchedRouteName()->willReturn('cli-generate-shortcode')->shouldBeCalledTimes(1);
        /** @var ServerRequestInterface $request */
        $request = null;

        $response = $middleware->__invoke(
            ServerRequestFactory::fromGlobals()->withAttribute(RouteResult::class, $routeResult->reveal()),
            $originalResponse,
            function ($req, $resp) use (&$invoked, &$request) {
                $invoked = true;
                $request = $req;
                return $resp;
            }
        );

        $this->assertSame($originalResponse, $response);
        $this->assertEquals($expectedLongUrl, $request->getAttribute('longUrl'));
        $this->assertTrue($invoked);
    }
}
