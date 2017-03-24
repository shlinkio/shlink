<?php
namespace ShlinkioTest\Shlink\Rest\ErrorHandler;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Rest\ErrorHandler\JsonErrorHandler;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;

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
    public function noErrorStatusReturnsInternalServerError()
    {
        $response = $this->errorHandler->__invoke(null, ServerRequestFactory::fromGlobals(), new Response());
        $this->assertInstanceOf(Response\JsonResponse::class, $response);
        $this->assertEquals(500, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function errorStatusReturnsThatStatus()
    {
        $response = $this->errorHandler->__invoke(
            null,
            ServerRequestFactory::fromGlobals(),
            (new Response())->withStatus(405)
        );
        $this->assertInstanceOf(Response\JsonResponse::class, $response);
        $this->assertEquals(405, $response->getStatusCode());
    }
}
