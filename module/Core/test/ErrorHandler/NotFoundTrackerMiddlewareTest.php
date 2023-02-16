<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ErrorHandler;

use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\ErrorHandler\Model\NotFoundType;
use Shlinkio\Shlink\Core\ErrorHandler\NotFoundTrackerMiddleware;
use Shlinkio\Shlink\Core\Visit\RequestTrackerInterface;

class NotFoundTrackerMiddlewareTest extends TestCase
{
    private NotFoundTrackerMiddleware $middleware;
    private ServerRequestInterface $request;
    private MockObject & RequestHandlerInterface $handler;
    private MockObject & RequestTrackerInterface $requestTracker;

    protected function setUp(): void
    {
        $this->handler = $this->createMock(RequestHandlerInterface::class);
        $this->requestTracker = $this->createMock(RequestTrackerInterface::class);
        $this->middleware = new NotFoundTrackerMiddleware($this->requestTracker);

        $this->request = ServerRequestFactory::fromGlobals()->withAttribute(
            NotFoundType::class,
            $this->createMock(NotFoundType::class),
        );
    }

    #[Test]
    public function delegatesIntoRequestTracker(): void
    {
        $this->handler->expects($this->once())->method('handle')->with($this->request);
        $this->requestTracker->expects($this->once())->method('trackNotFoundIfApplicable')->with($this->request);

        $this->middleware->process($this->request, $this->handler);
    }
}
