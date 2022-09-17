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
use Shlinkio\Shlink\Core\Action\RedirectAction;
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

use function str_starts_with;

class ExtraPathRedirectMiddlewareTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $resolver;
    private ObjectProphecy $requestTracker;
    private ObjectProphecy $redirectionBuilder;
    private ObjectProphecy $redirectResponseHelper;
    private ObjectProphecy $handler;

    protected function setUp(): void
    {
        $this->resolver = $this->prophesize(ShortUrlResolverInterface::class);
        $this->requestTracker = $this->prophesize(RequestTrackerInterface::class);
        $this->redirectionBuilder = $this->prophesize(ShortUrlRedirectionBuilderInterface::class);
        $this->redirectResponseHelper = $this->prophesize(RedirectResponseHelperInterface::class);
        $this->handler = $this->prophesize(RequestHandlerInterface::class);
        $this->handler->handle(Argument::cetera())->willReturn(new RedirectResponse(''));
    }

    /**
     * @test
     * @dataProvider provideNonRedirectingRequests
     */
    public function handlerIsCalledWhenConfigPreventsRedirectWithExtraPath(
        bool $appendExtraPath,
        bool $multiSegmentEnabled,
        ServerRequestInterface $request,
    ): void {
        $options = new UrlShortenerOptions(
            appendExtraPath: $appendExtraPath,
            multiSegmentSlugsEnabled: $multiSegmentEnabled,
        );

        $this->middleware($options)->process($request, $this->handler->reveal());

        $this->handler->handle($request)->shouldHaveBeenCalledOnce();
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

        yield 'disabled option' => [false, false, $buildReq(NotFoundType::fromRequest($baseReq, '/foo/bar'))];
        yield 'no error type' => [true, false, $buildReq(null)];
        yield 'base_url error' => [true, false, $buildReq(NotFoundType::fromRequest($baseReq, ''))];
        yield 'invalid_short_url error' => [
            true,
            false,
            $buildReq(NotFoundType::fromRequest($baseReq->withUri(new Uri('/foo'))->withAttribute(
                RouteResult::class,
                RouteResult::fromRoute(new Route(
                    '/foo',
                    $this->prophesize(MiddlewareInterface::class)->reveal(),
                    ['GET'],
                    RedirectAction::class,
                )),
            ), '')),
        ];
        yield 'regular_404 error with multi-segment slugs' => [
            true,
            true,
            $buildReq(NotFoundType::fromRequest($baseReq->withUri(new Uri('/foo'))->withAttribute(
                RouteResult::class,
                RouteResult::fromRouteFailure(['GET']),
            ), '')),
        ];
    }

    /**
     * @test
     * @dataProvider provideResolves
     */
    public function handlerIsCalledWhenNoShortUrlIsFoundAfterExpectedAmountOfIterations(
        bool $multiSegmentEnabled,
        int $expectedResolveCalls,
    ): void {
        $options = new UrlShortenerOptions(appendExtraPath: true, multiSegmentSlugsEnabled: $multiSegmentEnabled);

        $type = $this->prophesize(NotFoundType::class);
        $type->isRegularNotFound()->willReturn(true);
        $type->isInvalidShortUrl()->willReturn(true);
        $request = ServerRequestFactory::fromGlobals()->withAttribute(NotFoundType::class, $type->reveal())
                                                      ->withUri(new Uri('/shortCode/bar/baz'));

        $resolve = $this->resolver->resolveEnabledShortUrl(
            Argument::that(fn (ShortUrlIdentifier $identifier) => str_starts_with($identifier->shortCode, 'shortCode')),
        )->willThrow(ShortUrlNotFoundException::class);

        $this->middleware($options)->process($request, $this->handler->reveal());

        $resolve->shouldHaveBeenCalledTimes($expectedResolveCalls);
        $this->requestTracker->trackIfApplicable(Argument::cetera())->shouldNotHaveBeenCalled();
        $this->redirectionBuilder->buildShortUrlRedirect(Argument::cetera())->shouldNotHaveBeenCalled();
        $this->redirectResponseHelper->buildRedirectResponse(Argument::cetera())->shouldNotHaveBeenCalled();
    }

    /**
     * @test
     * @dataProvider provideResolves
     */
    public function visitIsTrackedAndRedirectIsReturnedWhenShortUrlIsFoundAfterExpectedAmountOfIterations(
        bool $multiSegmentEnabled,
        int $expectedResolveCalls,
        ?string $expectedExtraPath,
    ): void {
        $options = new UrlShortenerOptions(appendExtraPath: true, multiSegmentSlugsEnabled: $multiSegmentEnabled);

        $type = $this->prophesize(NotFoundType::class);
        $type->isRegularNotFound()->willReturn(true);
        $type->isInvalidShortUrl()->willReturn(true);
        $request = ServerRequestFactory::fromGlobals()->withAttribute(NotFoundType::class, $type->reveal())
                                                      ->withUri(new Uri('https://doma.in/shortCode/bar/baz'));
        $shortUrl = ShortUrl::withLongUrl('');
        $identifier = Argument::that(
            fn (ShortUrlIdentifier $identifier) => str_starts_with($identifier->shortCode, 'shortCode'),
        );

        $currentIteration = 1;
        $resolve = $this->resolver->resolveEnabledShortUrl($identifier)->will(
            function () use ($shortUrl, &$currentIteration, $expectedResolveCalls): ShortUrl {
                if ($expectedResolveCalls === $currentIteration) {
                    return $shortUrl;
                }

                $currentIteration++;
                throw ShortUrlNotFoundException::fromNotFound(ShortUrlIdentifier::fromShortUrl($shortUrl));
            },
        );
        $buildLongUrl = $this->redirectionBuilder->buildShortUrlRedirect($shortUrl, [], $expectedExtraPath)
                                                 ->willReturn('the_built_long_url');
        $buildResp = $this->redirectResponseHelper->buildRedirectResponse('the_built_long_url')->willReturn(
            new RedirectResponse(''),
        );

        $this->middleware($options)->process($request, $this->handler->reveal());

        $resolve->shouldHaveBeenCalledTimes($expectedResolveCalls);
        $buildLongUrl->shouldHaveBeenCalledOnce();
        $buildResp->shouldHaveBeenCalledOnce();
        $this->requestTracker->trackIfApplicable($shortUrl, $request)->shouldHaveBeenCalledOnce();
    }

    public function provideResolves(): iterable
    {
        yield [false, 1, '/bar/baz'];
        yield [true, 3, null];
    }

    private function middleware(?UrlShortenerOptions $options = null): ExtraPathRedirectMiddleware
    {
        return new ExtraPathRedirectMiddleware(
            $this->resolver->reveal(),
            $this->requestTracker->reveal(),
            $this->redirectionBuilder->reveal(),
            $this->redirectResponseHelper->reveal(),
            $options ?? new UrlShortenerOptions(appendExtraPath: true),
        );
    }
}
