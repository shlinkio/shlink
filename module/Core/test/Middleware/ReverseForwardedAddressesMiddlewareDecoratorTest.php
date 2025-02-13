<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Middleware;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\Middleware\ReverseForwardedAddressesMiddlewareDecorator;

class ReverseForwardedAddressesMiddlewareDecoratorTest extends TestCase
{
    private ReverseForwardedAddressesMiddlewareDecorator $middleware;
    private MockObject & MiddlewareInterface $decoratedMiddleware;
    private MockObject & RequestHandlerInterface $requestHandler;

    protected function setUp(): void
    {
        $this->decoratedMiddleware = $this->createMock(MiddlewareInterface::class);
        $this->requestHandler = $this->createMock(RequestHandlerInterface::class);
        $this->middleware = new ReverseForwardedAddressesMiddlewareDecorator($this->decoratedMiddleware);
    }

    #[Test]
    public function processesRequestAsIsWhenHeadersIsNotFound(): void
    {
        $request = ServerRequestFactory::fromGlobals();
        $this->decoratedMiddleware->expects($this->once())->method('process')->with(
            $request,
            $this->requestHandler,
        )->willReturn(new Response());

        $this->middleware->process($request, $this->requestHandler);
    }

    #[Test]
    public function revertsListOfAddressesWhenHeaderIsFound(): void
    {
        $request = ServerRequestFactory::fromGlobals()->withHeader(
            ReverseForwardedAddressesMiddlewareDecorator::FORWARDED_FOR_HEADER,
            '1.2.3.4,5.6.7.8,9.10.11.12',
        );

        $this->decoratedMiddleware->expects($this->once())->method('process')->with(
            $this->callback(fn (ServerRequestInterface $req): bool => $req->getHeaderLine(
                ReverseForwardedAddressesMiddlewareDecorator::FORWARDED_FOR_HEADER,
            ) === '9.10.11.12,5.6.7.8,1.2.3.4'),
            $this->requestHandler,
        )->willReturn(new Response());

        $this->middleware->process($request, $this->requestHandler);
    }
}
