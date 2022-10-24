<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ErrorHandler;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\ErrorHandler\Model\NotFoundType;
use Shlinkio\Shlink\Core\ErrorHandler\NotFoundTypeResolverMiddleware;

class NotFoundTypeResolverMiddlewareTest extends TestCase
{
    private NotFoundTypeResolverMiddleware $middleware;
    private MockObject & RequestHandlerInterface $handler;

    protected function setUp(): void
    {
        $this->middleware = new NotFoundTypeResolverMiddleware('');
        $this->handler = $this->createMock(RequestHandlerInterface::class);
    }

    /** @test */
    public function notFoundTypeIsAddedToRequest(): void
    {
        $request = ServerRequestFactory::fromGlobals();
        $this->handler->expects($this->once())->method('handle')->with(
            $this->callback(function (ServerRequestInterface $req): bool {
                Assert::assertArrayHasKey(NotFoundType::class, $req->getAttributes());
                return true;
            }),
        )->willReturn(new Response());

        $this->middleware->process($request, $this->handler);

        self::assertArrayNotHasKey(NotFoundType::class, $request->getAttributes());
    }
}
