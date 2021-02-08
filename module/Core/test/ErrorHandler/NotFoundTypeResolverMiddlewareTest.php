<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ErrorHandler;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\ErrorHandler\Model\NotFoundType;
use Shlinkio\Shlink\Core\ErrorHandler\NotFoundTypeResolverMiddleware;

class NotFoundTypeResolverMiddlewareTest extends TestCase
{
    use ProphecyTrait;

    private NotFoundTypeResolverMiddleware $middleware;
    private ObjectProphecy $handler;

    protected function setUp(): void
    {
        $this->middleware = new NotFoundTypeResolverMiddleware('');
        $this->handler = $this->prophesize(RequestHandlerInterface::class);
    }

    /** @test */
    public function notFoundTypeIsAddedToRequest(): void
    {
        $request = ServerRequestFactory::fromGlobals();
        $handle = $this->handler->handle(Argument::that(function (ServerRequestInterface $req) {
            Assert::assertArrayHasKey(NotFoundType::class, $req->getAttributes());

            return true;
        }))->willReturn(new Response());

        $this->middleware->process($request, $this->handler->reveal());

        self::assertArrayNotHasKey(NotFoundType::class, $request->getAttributes());
        $handle->shouldHaveBeenCalledOnce();
    }
}
