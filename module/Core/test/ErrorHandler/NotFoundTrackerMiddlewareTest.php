<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ErrorHandler;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\ErrorHandler\Model\NotFoundType;
use Shlinkio\Shlink\Core\ErrorHandler\NotFoundTrackerMiddleware;
use Shlinkio\Shlink\Core\Visit\RequestTrackerInterface;

class NotFoundTrackerMiddlewareTest extends TestCase
{
    use ProphecyTrait;

    private NotFoundTrackerMiddleware $middleware;
    private ServerRequestInterface $request;
    private ObjectProphecy $requestTracker;
    private ObjectProphecy $notFoundType;
    private ObjectProphecy $handler;

    protected function setUp(): void
    {
        $this->notFoundType = $this->prophesize(NotFoundType::class);
        $this->handler = $this->prophesize(RequestHandlerInterface::class);
        $this->handler->handle(Argument::cetera())->willReturn(new Response());

        $this->requestTracker = $this->prophesize(RequestTrackerInterface::class);
        $this->middleware = new NotFoundTrackerMiddleware($this->requestTracker->reveal());

        $this->request = ServerRequestFactory::fromGlobals()->withAttribute(
            NotFoundType::class,
            $this->notFoundType->reveal(),
        );
    }

    /** @test */
    public function delegatesIntoRequestTracker(): void
    {
        $this->middleware->process($this->request, $this->handler->reveal());

        $this->requestTracker->trackNotFoundIfApplicable($this->request)->shouldHaveBeenCalledOnce();
        $this->handler->handle($this->request)->shouldHaveBeenCalledOnce();
    }
}
