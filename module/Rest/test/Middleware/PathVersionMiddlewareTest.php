<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Middleware;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Rest\Middleware\PathVersionMiddleware;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Uri;

use function array_shift;

class PathVersionMiddlewareTest extends TestCase
{
    private PathVersionMiddleware $middleware;

    public function setUp(): void
    {
        $this->middleware = new PathVersionMiddleware();
    }

    /** @test */
    public function whenVersionIsProvidedRequestRemainsUnchanged(): void
    {
        $request = (new ServerRequest())->withUri(new Uri('/v2/foo'));

        $delegate = $this->prophesize(RequestHandlerInterface::class);
        $process = $delegate->handle($request)->willReturn(new Response());

        $this->middleware->process($request, $delegate->reveal());

        $process->shouldHaveBeenCalled();
    }

    /** @test */
    public function versionOneIsPrependedWhenNoVersionIsDefined(): void
    {
        $request = (new ServerRequest())->withUri(new Uri('/bar/baz'));

        $delegate = $this->prophesize(RequestHandlerInterface::class);
        $delegate->handle(Argument::type(Request::class))->will(function (array $args) use ($request) {
            $req = array_shift($args);

            Assert::assertNotSame($request, $req);
            Assert::assertEquals('/v1/bar/baz', $req->getUri()->getPath());
            return new Response();
        });


        $this->middleware->process($request, $delegate->reveal());
    }
}
