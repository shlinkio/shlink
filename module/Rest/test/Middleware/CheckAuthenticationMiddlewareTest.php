<?php
namespace ShlinkioTest\Shlink\Rest\Middleware;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Rest\Authentication\JWTService;
use Shlinkio\Shlink\Rest\Middleware\CheckAuthenticationMiddleware;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Expressive\Router\Route;
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
    protected $jwtService;

    public function setUp()
    {
        $this->jwtService = $this->prophesize(JWTService::class);
        $this->middleware = new CheckAuthenticationMiddleware($this->jwtService->reveal(), Translator::factory([]));
    }

    /**
     * @test
     */
    public function someWhiteListedSituationsFallbackToNextMiddleware()
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
            RouteResult::fromRoute(new Route('foo', '', Route::HTTP_METHOD_ANY, 'rest-authenticate'), [])
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
            RouteResult::fromRoute(new Route('bar', 'foo'), [])
        )->withMethod('OPTIONS');
        $response = new Response();
        $isCalled = false;
        $this->assertFalse($isCalled);
        $this->middleware->__invoke($request, $response, function ($req, $resp) use (&$isCalled) {
            $isCalled = true;
        });
        $this->assertTrue($isCalled);
    }

    /**
     * @test
     */
    public function noHeaderReturnsError()
    {
        $request = ServerRequestFactory::fromGlobals()->withAttribute(
            RouteResult::class,
            RouteResult::fromRoute(new Route('bar', 'foo'), [])
        );
        $response = $this->middleware->__invoke($request, new Response());
        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function provideAnAuthorizationWithoutTypeReturnsError()
    {
        $authToken = 'ABC-abc';
        $request = ServerRequestFactory::fromGlobals()->withAttribute(
            RouteResult::class,
            RouteResult::fromRoute(new Route('bar', 'foo'), [])
        )->withHeader(CheckAuthenticationMiddleware::AUTHORIZATION_HEADER, $authToken);

        $response = $this->middleware->__invoke($request, new Response());
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertTrue(strpos($response->getBody()->getContents(), 'You need to provide the Bearer type') > 0);
    }

    /**
     * @test
     */
    public function provideAnAuthorizationWithWrongTypeReturnsError()
    {
        $authToken = 'ABC-abc';
        $request = ServerRequestFactory::fromGlobals()->withAttribute(
            RouteResult::class,
            RouteResult::fromRoute(new Route('bar', 'foo'), [])
        )->withHeader(CheckAuthenticationMiddleware::AUTHORIZATION_HEADER, 'Basic ' . $authToken);

        $response = $this->middleware->__invoke($request, new Response());
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertTrue(
            strpos($response->getBody()->getContents(), 'Provided authorization type Basic is not supported') > 0
        );
    }

    /**
     * @test
     */
    public function provideAnExpiredTokenReturnsError()
    {
        $authToken = 'ABC-abc';
        $request = ServerRequestFactory::fromGlobals()->withAttribute(
            RouteResult::class,
            RouteResult::fromRoute(new Route('bar', 'foo'), [])
        )->withHeader(CheckAuthenticationMiddleware::AUTHORIZATION_HEADER, 'Bearer ' . $authToken);
        $this->jwtService->verify($authToken)->willReturn(false)->shouldBeCalledTimes(1);

        $response = $this->middleware->__invoke($request, new Response());
        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function provideCorrectTokenUpdatesExpirationAndFallbacksToNextMiddleware()
    {
        $authToken = 'ABC-abc';
        $request = ServerRequestFactory::fromGlobals()->withAttribute(
            RouteResult::class,
            RouteResult::fromRoute(new Route('bar', 'foo'), [])
        )->withHeader(CheckAuthenticationMiddleware::AUTHORIZATION_HEADER, 'bearer ' . $authToken);
        $this->jwtService->verify($authToken)->willReturn(true)->shouldBeCalledTimes(1);
        $this->jwtService->refresh($authToken)->willReturn($authToken)->shouldBeCalledTimes(1);

        $isCalled = false;
        $this->assertFalse($isCalled);
        $this->middleware->__invoke($request, new Response(), function ($req, $resp) use (&$isCalled) {
            $isCalled = true;
            return $resp;
        });
        $this->assertTrue($isCalled);
    }
}
