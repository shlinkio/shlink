<?php
namespace ShlinkioTest\Shlink\Rest\Middleware;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shlinkio\Shlink\Rest\Middleware\BodyParserMiddleware;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Stream;

class BodyParserMiddlewareTest extends TestCase
{
    /**
     * @var BodyParserMiddleware
     */
    private $middleware;

    public function setUp()
    {
        $this->middleware = new BodyParserMiddleware();
    }

    /**
     * @test
     */
    public function requestsFromOtherMethodsJustFallbackToNextMiddleware()
    {
        $request = ServerRequestFactory::fromGlobals()->withMethod('GET');
        $test = $this;
        $this->middleware->__invoke($request, new Response(), function ($req, $resp) use ($test, $request) {
            $test->assertSame($request, $req);
        });

        $request = $request->withMethod('POST');
        $test = $this;
        $this->middleware->__invoke($request, new Response(), function ($req, $resp) use ($test, $request) {
            $test->assertSame($request, $req);
        });
    }

    /**
     * @test
     */
    public function jsonRequestsAreJsonDecoded()
    {
        $body = new Stream('php://temp', 'wr');
        $body->write('{"foo": "bar", "bar": ["one", 5]}');
        $request = ServerRequestFactory::fromGlobals()->withMethod('PUT')
                                                      ->withBody($body)
                                                      ->withHeader('content-type', 'application/json');
        $test = $this;
        $this->middleware->__invoke($request, new Response(), function (Request $req, $resp) use ($test, $request) {
            $test->assertNotSame($request, $req);
            $test->assertEquals([
                'foo' => 'bar',
                'bar' => ['one', 5],
            ], $req->getParsedBody());
        });
    }

    /**
     * @test
     */
    public function regularRequestsAreUrlDecoded()
    {
        $body = new Stream('php://temp', 'wr');
        $body->write('foo=bar&bar[]=one&bar[]=5');
        $request = ServerRequestFactory::fromGlobals()->withMethod('PUT')
                                                      ->withBody($body);
        $test = $this;
        $this->middleware->__invoke($request, new Response(), function (Request $req, $resp) use ($test, $request) {
            $test->assertNotSame($request, $req);
            $test->assertEquals([
                'foo' => 'bar',
                'bar' => ['one', 5],
            ], $req->getParsedBody());
        });
    }
}
