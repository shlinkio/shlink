<?php
namespace ShlinkioTest\Shlink\Rest\Middleware;

use PHPUnit_Framework_TestCase as TestCase;
use Shlinkio\Shlink\Rest\Middleware\CrossDomainMiddleware;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;

class CrossDomainMiddlewareTest extends TestCase
{
    /**
     * @var CrossDomainMiddleware
     */
    protected $middleware;

    public function setUp()
    {
        $this->middleware = new CrossDomainMiddleware();
    }

    /**
     * @test
     */
    public function anyRequestIncludesTheAllowAccessHeader()
    {
        $response = $this->middleware->__invoke(
            ServerRequestFactory::fromGlobals(),
            new Response(),
            function ($req, $resp) {
                return $resp;
            }
        );

        $headers = $response->getHeaders();
        $this->assertArrayHasKey('Access-Control-Allow-Origin', $headers);
        $this->assertArrayNotHasKey('Access-Control-Allow-Headers', $headers);
    }

    /**
     * @test
     */
    public function optionsRequestIncludesMoreHeaders()
    {
        $request = ServerRequestFactory::fromGlobals(['REQUEST_METHOD' => 'OPTIONS']);

        $response = $this->middleware->__invoke($request, new Response(), function ($req, $resp) {
            return $resp;
        });

        $headers = $response->getHeaders();
        $this->assertArrayHasKey('Access-Control-Allow-Origin', $headers);
        $this->assertArrayHasKey('Access-Control-Allow-Headers', $headers);
    }
}
