<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ErrorHandler;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\ErrorHandler\Model\NotFoundType;
use Shlinkio\Shlink\Core\ErrorHandler\NotFoundTrackerMiddleware;
use Shlinkio\Shlink\Core\Visit\RequestTrackerInterface;

use const Shlinkio\Shlink\REDIRECT_URL_REQUEST_ATTRIBUTE;

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

    #[Test, DataProvider('provideResponses')]
    public function delegatesIntoRequestTracker(Response $resp, string|null $expectedRedirectUrl): void
    {
        $this->handler->expects($this->once())->method('handle')->with($this->request)->willReturn($resp);
        $this->requestTracker->expects($this->once())->method('trackNotFoundIfApplicable')->with(
            $this->request->withAttribute(REDIRECT_URL_REQUEST_ATTRIBUTE, $expectedRedirectUrl),
        );

        $result = $this->middleware->process($this->request, $this->handler);

        self::assertSame($resp, $result);
    }

    public static function provideResponses(): iterable
    {
        yield 'no location response' => [new Response(), null];
        yield 'location response' => [new Response\RedirectResponse('the_location'), 'the_location'];
    }
}
