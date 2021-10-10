<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ShortUrl\Middleware;

use Laminas\Diactoros\Response\RedirectResponse;
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
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ErrorHandler\Model\NotFoundType;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\Service\ShortUrl\ShortUrlResolverInterface;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlRedirectionBuilderInterface;
use Shlinkio\Shlink\Core\ShortUrl\Middleware\ExtraPathRedirectMiddleware;
use Shlinkio\Shlink\Core\Util\RedirectResponseHelperInterface;
use Shlinkio\Shlink\Core\Visit\RequestTrackerInterface;

class ExtraPathRedirectMiddlewareTest extends TestCase
{
    use ProphecyTrait;

    private ExtraPathRedirectMiddleware $middleware;
    private ObjectProphecy $resolver;
    private ObjectProphecy $requestTracker;
    private ObjectProphecy $redirectionBuilder;
    private ObjectProphecy $redirectResponseHelper;
    private UrlShortenerOptions $options;
    private ObjectProphecy $handler;

    protected function setUp(): void
    {
        $this->resolver = $this->prophesize(ShortUrlResolverInterface::class);
        $this->requestTracker = $this->prophesize(RequestTrackerInterface::class);
        $this->redirectionBuilder = $this->prophesize(ShortUrlRedirectionBuilderInterface::class);
        $this->redirectResponseHelper = $this->prophesize(RedirectResponseHelperInterface::class);
        $this->options = new UrlShortenerOptions(['append_extra_path' => true]);

        $this->middleware = new ExtraPathRedirectMiddleware(
            $this->resolver->reveal(),
            $this->requestTracker->reveal(),
            $this->redirectionBuilder->reveal(),
            $this->redirectResponseHelper->reveal(),
            $this->options,
        );

        $this->handler = $this->prophesize(RequestHandlerInterface::class);
        $this->handler->handle(Argument::cetera())->willReturn(new RedirectResponse(''));
    }

    /**
     * @test
     * @dataProvider provideNonRedirectingRequests
     */
    public function handlerIsCalledWhenConfigPreventsRedirectWithExtraPath(
        bool $appendExtraPath,
        ServerRequestInterface $request,
    ): void {
        $this->options->appendExtraPath = $appendExtraPath;

        $this->middleware->process($request, $this->handler->reveal());

        $this->resolver->resolveEnabledShortUrl(Argument::cetera())->shouldNotHaveBeenCalled();
        $this->requestTracker->trackIfApplicable(Argument::cetera())->shouldNotHaveBeenCalled();
        $this->redirectionBuilder->buildShortUrlRedirect(Argument::cetera())->shouldNotHaveBeenCalled();
        $this->redirectResponseHelper->buildRedirectResponse(Argument::cetera())->shouldNotHaveBeenCalled();
    }

    public function provideNonRedirectingRequests(): iterable
    {
        $baseReq = ServerRequestFactory::fromGlobals();
        $buildReq = static fn (?NotFoundType $type): ServerRequestInterface =>
            $baseReq->withAttribute(NotFoundType::class, $type);

        yield 'disabled option' => [false, $buildReq(NotFoundType::fromRequest($baseReq, '/foo/bar'))];
        yield 'base_url error' => [true, $buildReq(NotFoundType::fromRequest($baseReq, ''))];
        yield 'invalid_short_url error' => [
            true,
            $buildReq(NotFoundType::fromRequest($baseReq, ''))->withAttribute(
                RouteResult::class,
                RouteResult::fromRoute(new Route(
                    '',
                    $this->prophesize(MiddlewareInterface::class)->reveal(),
                    ['GET'],
                )),
            ),
        ];
        yield 'no error type' => [true, $buildReq(null)];
    }

    /** @test */
    public function handlerIsCalledWhenNoShortUrlIsFound(): void
    {
        $type = $this->prophesize(NotFoundType::class);
        $type->isRegularNotFound()->willReturn(true);
        $request = ServerRequestFactory::fromGlobals()->withAttribute(NotFoundType::class, $type->reveal())
                                                      ->withUri(new Uri('/shortCode/bar/baz'));

        $resolve = $this->resolver->resolveEnabledShortUrl(Argument::cetera())->willThrow(
            ShortUrlNotFoundException::class,
        );

        $this->middleware->process($request, $this->handler->reveal());

        $resolve->shouldHaveBeenCalledOnce();
        $this->requestTracker->trackIfApplicable(Argument::cetera())->shouldNotHaveBeenCalled();
        $this->redirectionBuilder->buildShortUrlRedirect(Argument::cetera())->shouldNotHaveBeenCalled();
        $this->redirectResponseHelper->buildRedirectResponse(Argument::cetera())->shouldNotHaveBeenCalled();
    }

    /** @test */
    public function visitIsTrackedAndRedirectIsReturnedWhenShortUrlIsFound(): void
    {
        $type = $this->prophesize(NotFoundType::class);
        $type->isRegularNotFound()->willReturn(true);
        $request = ServerRequestFactory::fromGlobals()->withAttribute(NotFoundType::class, $type->reveal())
                                                      ->withUri(new Uri('https://doma.in/shortCode/bar/baz'));
        $shortUrl = ShortUrl::withLongUrl('');
        $identifier = ShortUrlIdentifier::fromShortCodeAndDomain('shortCode', 'doma.in');

        $resolve = $this->resolver->resolveEnabledShortUrl($identifier)->willReturn($shortUrl);
        $buildLongUrl = $this->redirectionBuilder->buildShortUrlRedirect($shortUrl, [], '/bar/baz')->willReturn(
            'the_built_long_url',
        );
        $buildResp = $this->redirectResponseHelper->buildRedirectResponse('the_built_long_url')->willReturn(
            new RedirectResponse(''),
        );

        $this->middleware->process($request, $this->handler->reveal());

        $resolve->shouldHaveBeenCalledOnce();
        $buildLongUrl->shouldHaveBeenCalledOnce();
        $buildResp->shouldHaveBeenCalledOnce();
        $this->requestTracker->trackIfApplicable($shortUrl, $request)->shouldHaveBeenCalledOnce();
    }
}
