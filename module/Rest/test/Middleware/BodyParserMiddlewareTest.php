<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Middleware;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Rest\Middleware\BodyParserMiddleware;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Stream;

use function array_shift;

class BodyParserMiddlewareTest extends TestCase
{
    /** @var BodyParserMiddleware */
    private $middleware;

    public function setUp(): void
    {
        $this->middleware = new BodyParserMiddleware();
    }

    /**
     * @test
     * @dataProvider provideIgnoredRequestMethods
     */
    public function requestsFromOtherMethodsJustFallbackToNextMiddleware(string $method): void
    {
        $request = (new ServerRequest())->withMethod($method);
        $this->assertHandlingRequestJustFallsBackToNext($request);
    }

    public function provideIgnoredRequestMethods(): iterable
    {
        yield 'GET' => ['GET'];
        yield 'HEAD' => ['HEAD'];
        yield 'OPTIONS' => ['OPTIONS'];
    }

    /** @test */
    public function requestsWithNonEmptyBodyJustFallbackToNextMiddleware(): void
    {
        $request = (new ServerRequest())->withParsedBody(['foo' => 'bar'])->withMethod('POST');
        $this->assertHandlingRequestJustFallsBackToNext($request);
    }

    private function assertHandlingRequestJustFallsBackToNext(ServerRequestInterface $request): void
    {
        $nextHandler = $this->prophesize(RequestHandlerInterface::class);
        $handle = $nextHandler->handle($request)->willReturn(new Response());

        $this->middleware->process($request, $nextHandler->reveal());

        $handle->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function jsonRequestsAreJsonDecoded(): void
    {
        $test = $this;
        $body = new Stream('php://temp', 'wr');
        $body->write('{"foo": "bar", "bar": ["one", 5]}');
        $request = (new ServerRequest())->withMethod('PUT')
                                        ->withBody($body)
                                        ->withHeader('content-type', 'application/json');
        $delegate = $this->prophesize(RequestHandlerInterface::class);
        $process = $delegate->handle(Argument::type(ServerRequestInterface::class))->will(
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

        $process->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function regularRequestsAreUrlDecoded(): void
    {
        $test = $this;
        $body = new Stream('php://temp', 'wr');
        $body->write('foo=bar&bar[]=one&bar[]=5');
        $request = (new ServerRequest())->withMethod('PUT')
                                        ->withBody($body);
        $delegate = $this->prophesize(RequestHandlerInterface::class);
        $process = $delegate->handle(Argument::type(ServerRequestInterface::class))->will(
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

        $process->shouldHaveBeenCalledOnce();
    }
}
