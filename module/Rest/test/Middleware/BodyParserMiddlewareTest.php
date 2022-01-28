<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Middleware;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Stream;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ProphecyInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Rest\Middleware\BodyParserMiddleware;

use function array_shift;

class BodyParserMiddlewareTest extends TestCase
{
    use ProphecyTrait;

    private BodyParserMiddleware $middleware;

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
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn($method);
        $request->getParsedBody()->willReturn([]);

        self::assertHandlingRequestJustFallsBackToNext($request);
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
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn('POST');
        $request->getParsedBody()->willReturn(['foo' => 'bar']);

        self::assertHandlingRequestJustFallsBackToNext($request);
    }

    private function assertHandlingRequestJustFallsBackToNext(ProphecyInterface $requestMock): void
    {
        $getContentType = $requestMock->getHeaderLine('Content-type')->willReturn('');
        $request = $requestMock->reveal();

        $nextHandler = $this->prophesize(RequestHandlerInterface::class);
        $handle = $nextHandler->handle($request)->willReturn(new Response());

        $this->middleware->process($request, $nextHandler->reveal());

        $handle->shouldHaveBeenCalledOnce();
        $getContentType->shouldNotHaveBeenCalled();
    }

    /** @test */
    public function jsonRequestsAreJsonDecoded(): void
    {
        $test = $this;
        $body = new Stream('php://temp', 'wr');
        $body->write('{"foo": "bar", "bar": ["one", 5]}');
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
            },
        );

        $this->middleware->process($request, $delegate->reveal());

        $process->shouldHaveBeenCalledOnce();
    }
}
