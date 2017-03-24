<?php
namespace ShlinkioTest\Shlink\Rest\Middleware;

use PHPUnit\Framework\TestCase;
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
    public function nonCrossDomainRequestsAreNotAffected()
    {
        $originalResponse = new Response();
        $response = $this->middleware->__invoke(
            ServerRequestFactory::fromGlobals(),
            $originalResponse,
            function ($req, $resp) {
                return $resp;
            }
        );
        $this->assertSame($originalResponse, $response);

        $headers = $response->getHeaders();
        $this->assertArrayNotHasKey('Access-Control-Allow-Origin', $headers);
        $this->assertArrayNotHasKey('Access-Control-Allow-Headers', $headers);
    }

    /**
     * @test
     */
    public function anyRequestIncludesTheAllowAccessHeader()
    {
        $originalResponse = new Response();
        $response = $this->middleware->__invoke(
            ServerRequestFactory::fromGlobals()->withHeader('Origin', 'local'),
            $originalResponse,
            function ($req, $resp) {
                return $resp;
            }
        );
        $this->assertNotSame($originalResponse, $response);

        $headers = $response->getHeaders();
        $this->assertArrayHasKey('Access-Control-Allow-Origin', $headers);
        $this->assertArrayNotHasKey('Access-Control-Allow-Headers', $headers);
    }

    /**
     * @test
     */
    public function optionsRequestIncludesMoreHeaders()
    {
        $originalResponse = new Response();
        $request = ServerRequestFactory::fromGlobals(['REQUEST_METHOD' => 'OPTIONS'])->withHeader('Origin', 'local');

        $response = $this->middleware->__invoke($request, $originalResponse, function ($req, $resp) {
            return $resp;
        });
        $this->assertNotSame($originalResponse, $response);

        $headers = $response->getHeaders();
        $this->assertArrayHasKey('Access-Control-Allow-Origin', $headers);
        $this->assertArrayHasKey('Access-Control-Allow-Headers', $headers);
    }
}
