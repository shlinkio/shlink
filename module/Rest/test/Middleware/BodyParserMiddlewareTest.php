<?php
namespace ShlinkioTest\Shlink\Rest\Middleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\MethodProphecy;
use Psr\Http\Message\ServerRequestInterface;
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
        $delegate = $this->prophesize(DelegateInterface::class);
        /** @var MethodProphecy $process */
        $process = $delegate->process($request)->willReturn(new Response());

        $this->middleware->process($request, $delegate->reveal());

        $process->shouldHaveBeenCalledTimes(1);
    }

    /**
     * @test
     */
    public function jsonRequestsAreJsonDecoded()
    {
        $test = $this;
        $body = new Stream('php://temp', 'wr');
        $body->write('{"foo": "bar", "bar": ["one", 5]}');
        $request = ServerRequestFactory::fromGlobals()->withMethod('PUT')
                                                      ->withBody($body)
                                                      ->withHeader('content-type', 'application/json');
        $delegate = $this->prophesize(DelegateInterface::class);
        /** @var MethodProphecy $process */
        $process = $delegate->process(Argument::type(ServerRequestInterface::class))->will(
            function (array $args) use ($test) {
                /** @var ServerRequestInterface $req */
                $req = array_shift($args);

                $test->assertEquals([
                    'foo' => 'bar',
                    'bar' => ['one', 5],
                ], $req->getParsedBody());

                return new Response();
            }
        );

        $this->middleware->process($request, $delegate->reveal());

        $process->shouldHaveBeenCalledTimes(1);
    }

    /**
     * @test
     */
    public function regularRequestsAreUrlDecoded()
    {
        $test = $this;
        $body = new Stream('php://temp', 'wr');
        $body->write('foo=bar&bar[]=one&bar[]=5');
        $request = ServerRequestFactory::fromGlobals()->withMethod('PUT')
                                                      ->withBody($body);
        $delegate = $this->prophesize(DelegateInterface::class);
        /** @var MethodProphecy $process */
        $process = $delegate->process(Argument::type(ServerRequestInterface::class))->will(
            function (array $args) use ($test) {
                /** @var ServerRequestInterface $req */
                $req = array_shift($args);

                $test->assertEquals([
                    'foo' => 'bar',
                    'bar' => ['one', 5],
                ], $req->getParsedBody());

                return new Response();
            }
        );

        $this->middleware->process($request, $delegate->reveal());

        $process->shouldHaveBeenCalledTimes(1);
    }
}
