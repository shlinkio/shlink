<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\EventDispatcher\Helper;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Common\Middleware\RequestIdMiddleware;
use Shlinkio\Shlink\Core\EventDispatcher\Helper\RequestIdProvider;

class RequestIdProviderTest extends TestCase
{
    private RequestIdProvider $provider;
    private RequestIdMiddleware $middleware;

    protected function setUp(): void
    {
        $this->middleware = new RequestIdMiddleware();
        $this->provider = new RequestIdProvider($this->middleware);
    }

    #[Test]
    public function requestIdTrackedByMiddlewareIsForwarded(): void
    {
        $initialId = $this->middleware->currentRequestId();
        self::assertEquals($initialId, $this->provider->currentRequestId());

        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn(new Response());
        $this->middleware->process(ServerRequestFactory::fromGlobals(), $handler);
        $idAfterProcessingRequest = $this->middleware->currentRequestId();
        self::assertNotEquals($idAfterProcessingRequest, $initialId);
        self::assertEquals($idAfterProcessingRequest, $this->provider->currentRequestId());

        $manuallySetId = 'foobar';
        $this->middleware->setCurrentRequestId($manuallySetId);
        self::assertNotEquals($manuallySetId, $idAfterProcessingRequest);
        self::assertEquals($manuallySetId, $this->provider->currentRequestId());
    }
}
