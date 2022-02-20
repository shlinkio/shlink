<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Middleware\Mercure;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;
use Mezzio\ProblemDetails\ProblemDetailsResponseFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Rest\Exception\MercureException;
use Shlinkio\Shlink\Rest\Middleware\Mercure\NotConfiguredMercureErrorHandler;

class NotConfiguredMercureErrorHandlerTest extends TestCase
{
    use ProphecyTrait;

    private NotConfiguredMercureErrorHandler $middleware;
    private ObjectProphecy $respFactory;
    private ObjectProphecy $logger;
    private ObjectProphecy $handler;

    protected function setUp(): void
    {
        $this->respFactory = $this->prophesize(ProblemDetailsResponseFactory::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->middleware = new NotConfiguredMercureErrorHandler($this->respFactory->reveal(), $this->logger->reveal());
        $this->handler = $this->prophesize(RequestHandlerInterface::class);
    }

    /** @test */
    public function requestHandlerIsInvokedWhenNotErrorOccurs(): void
    {
        $req = ServerRequestFactory::fromGlobals();
        $handle = $this->handler->handle($req)->willReturn(new Response());

        $this->middleware->process($req, $this->handler->reveal());

        $handle->shouldHaveBeenCalledOnce();
        $this->logger->warning(Argument::cetera())->shouldNotHaveBeenCalled();
        $this->respFactory->createResponseFromThrowable(Argument::cetera())->shouldNotHaveBeenCalled();
    }

    /** @test */
    public function exceptionIsParsedToResponse(): void
    {
        $req = ServerRequestFactory::fromGlobals();
        $handle = $this->handler->handle($req)->willThrow(MercureException::mercureNotConfigured());
        $createResp = $this->respFactory->createResponseFromThrowable(Argument::cetera())->willReturn(new Response());

        $this->middleware->process($req, $this->handler->reveal());

        $handle->shouldHaveBeenCalledOnce();
        $createResp->shouldHaveBeenCalledOnce();
        $this->logger->warning(Argument::cetera())->shouldHaveBeenCalledOnce();
    }
}
