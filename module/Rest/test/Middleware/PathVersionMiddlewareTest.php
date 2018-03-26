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
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Uri;

class PathVersionMiddlewareTest extends TestCase
{
    /**
     * @var PathVersionMiddleware
     */
    protected $middleware;

    public function setUp()
    {
        $this->middleware = new PathVersionMiddleware();
    }

    /**
     * @test
     */
    public function whenVersionIsProvidedRequestRemainsUnchanged()
    {
        $request = ServerRequestFactory::fromGlobals()->withUri(new Uri('/rest/v2/foo'));

        $delegate = $this->prophesize(RequestHandlerInterface::class);
        $process = $delegate->handle($request)->willReturn(new Response());

        $this->middleware->process($request, $delegate->reveal());

        $process->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function whenPathDoesNotStartWithRestRemainsUnchanged()
    {
        $request = ServerRequestFactory::fromGlobals()->withUri(new Uri('/foo'));

        $delegate = $this->prophesize(RequestHandlerInterface::class);
        $process = $delegate->handle($request)->willReturn(new Response());

        $this->middleware->process($request, $delegate->reveal());

        $process->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function versionOneIsPrependedWhenNoVersionIsDefined()
    {
        $request = ServerRequestFactory::fromGlobals()->withUri(new Uri('/rest/bar/baz'));

        $delegate = $this->prophesize(RequestHandlerInterface::class);
        $delegate->handle(Argument::type(Request::class))->will(function (array $args) use ($request) {
            $req = \array_shift($args);

            Assert::assertNotSame($request, $req);
            Assert::assertEquals('/rest/v1/bar/baz', $req->getUri()->getPath());
            return new Response();
        });


        $this->middleware->process($request, $delegate->reveal());
    }
}
