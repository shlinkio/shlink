<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Middleware\ShortUrl;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Rest\Middleware\ShortUrl\ShortCodePathMiddleware;
use Zend\Diactoros\Response;
use Zend\Diactoros\Uri;

class ShortCodePathMiddlewareTest extends TestCase
{
    private $middleware;
    private $requestHandler;

    public function setUp(): void
    {
        $this->middleware = new ShortCodePathMiddleware();
        $this->requestHandler = $this->prophesize(RequestHandlerInterface::class);
        $this->requestHandler->handle(Argument::type(ServerRequestInterface::class))->willReturn(new Response());
    }

    /** @test */
    public function properlyReplacesTheOldPathByTheNewOne()
    {
        $uri = new Uri('/short-codes/foo');

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getUri()->willReturn($uri);
        $withUri = $request->withUri(Argument::that(function (UriInterface $uri) {
            $path = $uri->getPath();

            Assert::assertStringContainsString('/short-urls', $path);
            Assert::assertStringNotContainsString('/short-codes', $path);

            return $uri;
        }))->willReturn($request->reveal());

        $this->middleware->process($request->reveal(), $this->requestHandler->reveal());

        $withUri->shouldHaveBeenCalledOnce();
    }
}
