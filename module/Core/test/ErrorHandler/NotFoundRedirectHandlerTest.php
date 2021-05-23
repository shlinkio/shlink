<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ErrorHandler;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\Uri;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\Action\RedirectAction;
use Shlinkio\Shlink\Core\ErrorHandler\Model\NotFoundType;
use Shlinkio\Shlink\Core\ErrorHandler\NotFoundRedirectHandler;
use Shlinkio\Shlink\Core\Options\NotFoundRedirectOptions;
use Shlinkio\Shlink\Core\Util\RedirectResponseHelperInterface;

class NotFoundRedirectHandlerTest extends TestCase
{
    use ProphecyTrait;

    private NotFoundRedirectHandler $middleware;
    private NotFoundRedirectOptions $redirectOptions;
    private ObjectProphecy $helper;

    public function setUp(): void
    {
        $this->redirectOptions = new NotFoundRedirectOptions();
        $this->helper = $this->prophesize(RedirectResponseHelperInterface::class);
        $this->middleware = new NotFoundRedirectHandler($this->redirectOptions, $this->helper->reveal());
    }

    /**
     * @test
     * @dataProvider provideRedirects
     */
    public function expectedRedirectionIsReturnedDependingOnTheCase(
        ServerRequestInterface $request,
        string $expectedRedirectTo,
    ): void {
        $this->redirectOptions->invalidShortUrl = 'invalidShortUrl';
        $this->redirectOptions->regular404 = 'regular404';
        $this->redirectOptions->baseUrl = 'baseUrl';

        $expectedResp = new Response();
        $buildResp = $this->helper->buildRedirectResponse($expectedRedirectTo)->willReturn($expectedResp);

        $next = $this->prophesize(RequestHandlerInterface::class);
        $handle = $next->handle($request)->willReturn(new Response());

        $resp = $this->middleware->process($request, $next->reveal());

        self::assertSame($expectedResp, $resp);
        $buildResp->shouldHaveBeenCalledOnce();
        $handle->shouldNotHaveBeenCalled();
    }

    public function provideRedirects(): iterable
    {
        yield 'base URL with trailing slash' => [
            $this->withNotFoundType(ServerRequestFactory::fromGlobals()->withUri(new Uri('/'))),
            'baseUrl',
        ];
        yield 'base URL without trailing slash' => [
            $this->withNotFoundType(ServerRequestFactory::fromGlobals()->withUri(new Uri(''))),
            'baseUrl',
        ];
        yield 'regular 404' => [
            $this->withNotFoundType(ServerRequestFactory::fromGlobals()->withUri(new Uri('/foo/bar'))),
            'regular404',
        ];
        yield 'invalid short URL' => [
            $this->withNotFoundType(ServerRequestFactory::fromGlobals()
                ->withAttribute(
                    RouteResult::class,
                    RouteResult::fromRoute(
                        new Route(
                            '',
                            $this->prophesize(MiddlewareInterface::class)->reveal(),
                            ['GET'],
                            RedirectAction::class,
                        ),
                    ),
                )
                ->withUri(new Uri('/abc123'))),
            'invalidShortUrl',
        ];
    }

    /** @test */
    public function nextMiddlewareIsInvokedWhenNotRedirectNeedsToOccur(): void
    {
        $req = $this->withNotFoundType(ServerRequestFactory::fromGlobals());
        $resp = new Response();

        $buildResp = $this->helper->buildRedirectResponse(Argument::cetera());

        $next = $this->prophesize(RequestHandlerInterface::class);
        $handle = $next->handle($req)->willReturn($resp);

        $result = $this->middleware->process($req, $next->reveal());

        self::assertSame($resp, $result);
        $buildResp->shouldNotHaveBeenCalled();
        $handle->shouldHaveBeenCalledOnce();
    }

    private function withNotFoundType(ServerRequestInterface $req): ServerRequestInterface
    {
        $type = NotFoundType::fromRequest($req, '');
        return $req->withAttribute(NotFoundType::class, $type);
    }
}
