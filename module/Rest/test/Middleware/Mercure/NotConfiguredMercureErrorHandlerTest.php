<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Middleware\Mercure;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;
use Mezzio\ProblemDetails\ProblemDetailsResponseFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Rest\Exception\MercureException;
use Shlinkio\Shlink\Rest\Middleware\Mercure\NotConfiguredMercureErrorHandler;

class NotConfiguredMercureErrorHandlerTest extends TestCase
{
    private NotConfiguredMercureErrorHandler $middleware;
    private MockObject & ProblemDetailsResponseFactory $respFactory;
    private MockObject & LoggerInterface $logger;
    private MockObject & RequestHandlerInterface $handler;

    protected function setUp(): void
    {
        $this->respFactory = $this->createMock(ProblemDetailsResponseFactory::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->middleware = new NotConfiguredMercureErrorHandler($this->respFactory, $this->logger);
        $this->handler = $this->createMock(RequestHandlerInterface::class);
    }

    /** @test */
    public function requestHandlerIsInvokedWhenNotErrorOccurs(): void
    {
        $req = ServerRequestFactory::fromGlobals();
        $this->handler->expects($this->once())->method('handle')->with($req)->willReturn(new Response());
        $this->respFactory->expects($this->never())->method('createResponseFromThrowable');
        $this->logger->expects($this->never())->method('warning');

        $this->middleware->process($req, $this->handler);
    }

    /** @test */
    public function exceptionIsParsedToResponse(): void
    {
        $req = ServerRequestFactory::fromGlobals();
        $this->handler->expects($this->once())->method('handle')->with($req)->willThrowException(
            MercureException::mercureNotConfigured(),
        );
        $this->respFactory->expects($this->once())->method('createResponseFromThrowable')->willReturn(new Response());
        $this->logger->expects($this->once())->method('warning');

        $this->middleware->process($req, $this->handler);
    }
}
