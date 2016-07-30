<?php
namespace ShlinkioTest\Shlink\Rest\ErrorHandler;

use PHPUnit_Framework_TestCase as TestCase;
use Shlinkio\Shlink\Rest\ErrorHandler\JsonErrorHandler;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Expressive\Router\RouteResult;

class JsonErrorHandlerTest extends TestCase
{
    /**
     * @var JsonErrorHandler
     */
    protected $errorHandler;

    public function setUp()
    {
        $this->errorHandler = new JsonErrorHandler();
    }

    /**
     * @test
     */
    public function noMatchedRouteReturnsNotFoundResponse()
    {
        $response = $this->errorHandler->__invoke(ServerRequestFactory::fromGlobals(), new Response());
        $this->assertInstanceOf(Response\JsonResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function matchedRouteWithErrorReturnsMethodNotAllowedResponse()
    {
        $response = $this->errorHandler->__invoke(
            ServerRequestFactory::fromGlobals(),
            (new Response())->withStatus(405),
            405
        );
        $this->assertInstanceOf(Response\JsonResponse::class, $response);
        $this->assertEquals(405, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function responseWithErrorKeepsStatus()
    {
        $response = $this->errorHandler->__invoke(
            ServerRequestFactory::fromGlobals()->withAttribute(
                RouteResult::class,
                RouteResult::fromRouteMatch('foo', 'bar', [])
            ),
            (new Response())->withStatus(401),
            401
        );
        $this->assertInstanceOf(Response\JsonResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function responseWithoutErrorReturnsStatus500()
    {
        $response = $this->errorHandler->__invoke(
            ServerRequestFactory::fromGlobals()->withAttribute(
                RouteResult::class,
                RouteResult::fromRouteMatch('foo', 'bar', [])
            ),
            (new Response())->withStatus(200),
            'Some error'
        );
        $this->assertInstanceOf(Response\JsonResponse::class, $response);
        $this->assertEquals(500, $response->getStatusCode());
    }
}
