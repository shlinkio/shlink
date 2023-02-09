<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Middleware;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Stream;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\Exception\MalformedBodyException;
use Shlinkio\Shlink\Rest\Middleware\BodyParserMiddleware;

class BodyParserMiddlewareTest extends TestCase
{
    private BodyParserMiddleware $middleware;

    protected function setUp(): void
    {
        $this->middleware = new BodyParserMiddleware();
    }

    /**
     * @test
     * @dataProvider provideIgnoredRequestMethods
     */
    public function requestsFromOtherMethodsJustFallbackToNextMiddleware(string $method): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn($method);
        $request->method('getParsedBody')->willReturn([]);

        $this->assertHandlingRequestJustFallsBackToNext($request);
    }

    public static function provideIgnoredRequestMethods(): iterable
    {
        yield 'GET' => ['GET'];
        yield 'HEAD' => ['HEAD'];
        yield 'OPTIONS' => ['OPTIONS'];
    }

    /** @test */
    public function requestsWithNonEmptyBodyJustFallbackToNextMiddleware(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn('POST');
        $request->method('getParsedBody')->willReturn(['foo' => 'bar']);

        $this->assertHandlingRequestJustFallsBackToNext($request);
    }

    private function assertHandlingRequestJustFallsBackToNext(MockObject & ServerRequestInterface $request): void
    {
        $request->expects($this->never())->method('getHeaderLine');

        $nextHandler = $this->createMock(RequestHandlerInterface::class);
        $nextHandler->expects($this->once())->method('handle')->with($request)->willReturn(new Response());

        $this->middleware->process($request, $nextHandler);
    }

    /** @test */
    public function jsonRequestsAreJsonDecoded(): void
    {
        $body = new Stream('php://temp', 'wr');
        $body->write('{"foo": "bar", "bar": ["one", 5]}');
        $request = (new ServerRequest())->withMethod('PUT')
                                        ->withBody($body);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->with(
            $this->isInstanceOf(ServerRequestInterface::class),
        )->willReturnCallback(function (ServerRequestInterface $req) {
            Assert::assertEquals([
                'foo' => 'bar',
                'bar' => ['one', 5],
            ], $req->getParsedBody());

            return new Response();
        });

        $this->middleware->process($request, $handler);
    }

    /** @test */
    public function invalidBodyResultsInException(): void
    {
        $body = new Stream('php://temp', 'wr');
        $body->write('{"foo": "bar", "bar": ["one');
        $request = (new ServerRequest())->withMethod('PUT')
                                        ->withBody($body);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $this->expectException(MalformedBodyException::class);
        $this->expectExceptionMessage('Provided request does not contain a valid JSON body.');

        $this->middleware->process($request, $handler);
    }
}
