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
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Visit\VisitsTrackerInterface;

class NotFoundTrackerMiddlewareTest extends TestCase
{
    use ProphecyTrait;

    private NotFoundTrackerMiddleware $middleware;
    private ServerRequestInterface $request;
    private ObjectProphecy $visitsTracker;
    private ObjectProphecy $notFoundType;
    private ObjectProphecy $handler;

    protected function setUp(): void
    {
        $this->notFoundType = $this->prophesize(NotFoundType::class);
        $this->handler = $this->prophesize(RequestHandlerInterface::class);
        $this->handler->handle(Argument::cetera())->willReturn(new Response());

        $this->visitsTracker = $this->prophesize(VisitsTrackerInterface::class);
        $this->middleware = new NotFoundTrackerMiddleware($this->visitsTracker->reveal());

        $this->request = ServerRequestFactory::fromGlobals()->withAttribute(
            NotFoundType::class,
            $this->notFoundType->reveal(),
        );
    }

    /** @test */
    public function baseUrlErrorIsTracked(): void
    {
        $isBaseUrl = $this->notFoundType->isBaseUrl()->willReturn(true);
        $isRegularNotFound = $this->notFoundType->isRegularNotFound()->willReturn(false);
        $isInvalidShortUrl = $this->notFoundType->isInvalidShortUrl()->willReturn(false);

        $this->middleware->process($this->request, $this->handler->reveal());

        $isBaseUrl->shouldHaveBeenCalledOnce();
        $isRegularNotFound->shouldNotHaveBeenCalled();
        $isInvalidShortUrl->shouldNotHaveBeenCalled();
        $this->visitsTracker->trackBaseUrlVisit(Argument::type(Visitor::class))->shouldHaveBeenCalledOnce();
        $this->visitsTracker->trackRegularNotFoundVisit(Argument::type(Visitor::class))->shouldNotHaveBeenCalled();
        $this->visitsTracker->trackInvalidShortUrlVisit(Argument::type(Visitor::class))->shouldNotHaveBeenCalled();
    }

    /** @test */
    public function regularNotFoundErrorIsTracked(): void
    {
        $isBaseUrl = $this->notFoundType->isBaseUrl()->willReturn(false);
        $isRegularNotFound = $this->notFoundType->isRegularNotFound()->willReturn(true);
        $isInvalidShortUrl = $this->notFoundType->isInvalidShortUrl()->willReturn(false);

        $this->middleware->process($this->request, $this->handler->reveal());

        $isBaseUrl->shouldHaveBeenCalledOnce();
        $isRegularNotFound->shouldHaveBeenCalledOnce();
        $isInvalidShortUrl->shouldNotHaveBeenCalled();
        $this->visitsTracker->trackBaseUrlVisit(Argument::type(Visitor::class))->shouldNotHaveBeenCalled();
        $this->visitsTracker->trackRegularNotFoundVisit(Argument::type(Visitor::class))->shouldHaveBeenCalledOnce();
        $this->visitsTracker->trackInvalidShortUrlVisit(Argument::type(Visitor::class))->shouldNotHaveBeenCalled();
    }

    /** @test */
    public function invalidShortUrlErrorIsTracked(): void
    {
        $isBaseUrl = $this->notFoundType->isBaseUrl()->willReturn(false);
        $isRegularNotFound = $this->notFoundType->isRegularNotFound()->willReturn(false);
        $isInvalidShortUrl = $this->notFoundType->isInvalidShortUrl()->willReturn(true);

        $this->middleware->process($this->request, $this->handler->reveal());

        $isBaseUrl->shouldHaveBeenCalledOnce();
        $isRegularNotFound->shouldHaveBeenCalledOnce();
        $isInvalidShortUrl->shouldHaveBeenCalledOnce();
        $this->visitsTracker->trackBaseUrlVisit(Argument::type(Visitor::class))->shouldNotHaveBeenCalled();
        $this->visitsTracker->trackRegularNotFoundVisit(Argument::type(Visitor::class))->shouldNotHaveBeenCalled();
        $this->visitsTracker->trackInvalidShortUrlVisit(Argument::type(Visitor::class))->shouldHaveBeenCalledOnce();
    }
}
