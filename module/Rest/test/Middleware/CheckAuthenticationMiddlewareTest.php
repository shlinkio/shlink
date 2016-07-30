<?php
namespace ShlinkioTest\Shlink\Rest\Middleware;

use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Rest\Middleware\CheckAuthenticationMiddleware;
use Shlinkio\Shlink\Rest\Service\RestTokenService;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Expressive\Router\RouteResult;
use Zend\I18n\Translator\Translator;

class CheckAuthenticationMiddlewareTest extends TestCase
{
    /**
     * @var CheckAuthenticationMiddleware
     */
    protected $middleware;
    /**
     * @var ObjectProphecy
     */
    protected $tokenService;

    public function setUp()
    {
        $this->tokenService = $this->prophesize(RestTokenService::class);
        $this->middleware = new CheckAuthenticationMiddleware($this->tokenService->reveal(), Translator::factory([]));
    }

    /**
     * @test
     */
    public function someWhitelistedSituationsFallbackToNextMiddleware()
    {
        $request = ServerRequestFactory::fromGlobals();
        $response = new Response();
        $isCalled = false;
        $this->assertFalse($isCalled);
        $this->middleware->__invoke($request, $response, function ($req, $resp) use (&$isCalled) {
            $isCalled = true;
        });
        $this->assertTrue($isCalled);

        $request = ServerRequestFactory::fromGlobals()->withAttribute(
            RouteResult::class,
            RouteResult::fromRouteFailure(['GET'])
        );
        $response = new Response();
        $isCalled = false;
        $this->assertFalse($isCalled);
        $this->middleware->__invoke($request, $response, function ($req, $resp) use (&$isCalled) {
            $isCalled = true;
        });
        $this->assertTrue($isCalled);

        $request = ServerRequestFactory::fromGlobals()->withAttribute(
            RouteResult::class,
            RouteResult::fromRouteMatch('rest-authenticate', 'foo', [])
        );
        $response = new Response();
        $isCalled = false;
        $this->assertFalse($isCalled);
        $this->middleware->__invoke($request, $response, function ($req, $resp) use (&$isCalled) {
            $isCalled = true;
        });
        $this->assertTrue($isCalled);

        $request = ServerRequestFactory::fromGlobals()->withAttribute(
            RouteResult::class,
            RouteResult::fromRouteMatch('bar', 'foo', [])
        )->withMethod('OPTIONS');
        $response = new Response();
        $isCalled = false;
        $this->assertFalse($isCalled);
        $this->middleware->__invoke($request, $response, function ($req, $resp) use (&$isCalled) {
            $isCalled = true;
        });
        $this->assertTrue($isCalled);
    }
}
