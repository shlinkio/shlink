<?php
namespace ShlinkioTest\Shlink\Rest\Middleware;

use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Entity\RestToken;
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

    /**
     * @test
     */
    public function noHeaderReturnsError()
    {
        $request = ServerRequestFactory::fromGlobals()->withAttribute(
            RouteResult::class,
            RouteResult::fromRouteMatch('bar', 'foo', [])
        );
        $response = $this->middleware->__invoke($request, new Response());
        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function provideAnExpiredTokenReturnsError()
    {
        $authToken = 'ABC-abc';
        $request = ServerRequestFactory::fromGlobals()->withAttribute(
            RouteResult::class,
            RouteResult::fromRouteMatch('bar', 'foo', [])
        )->withHeader(CheckAuthenticationMiddleware::AUTH_TOKEN_HEADER, $authToken);
        $this->tokenService->getByToken($authToken)->willReturn(
            (new RestToken())->setExpirationDate((new \DateTime())->sub(new \DateInterval('P1D')))
        )->shouldBeCalledTimes(1);

        $response = $this->middleware->__invoke($request, new Response());
        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function provideCorrectTokenUpdatesExpirationAndFallbacksToNextMiddleware()
    {
        $authToken = 'ABC-abc';
        $restToken = (new RestToken())->setExpirationDate((new \DateTime())->add(new \DateInterval('P1D')));
        $request = ServerRequestFactory::fromGlobals()->withAttribute(
            RouteResult::class,
            RouteResult::fromRouteMatch('bar', 'foo', [])
        )->withHeader(CheckAuthenticationMiddleware::AUTH_TOKEN_HEADER, $authToken);
        $this->tokenService->getByToken($authToken)->willReturn($restToken)->shouldBeCalledTimes(1);
        $this->tokenService->updateExpiration($restToken)->shouldBeCalledTimes(1);

        $isCalled = false;
        $this->assertFalse($isCalled);
        $this->middleware->__invoke($request, new Response(), function ($req, $resp) use (&$isCalled) {
            $isCalled = true;
        });
        $this->assertTrue($isCalled);
    }
}
