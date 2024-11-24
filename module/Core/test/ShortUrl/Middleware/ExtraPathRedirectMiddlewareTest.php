<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ShortUrl\Middleware;

use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\Uri;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\Action\RedirectAction;
use Shlinkio\Shlink\Core\Config\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\ErrorHandler\Model\NotFoundType;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlRedirectionBuilderInterface;
use Shlinkio\Shlink\Core\ShortUrl\Middleware\ExtraPathRedirectMiddleware;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlResolverInterface;
use Shlinkio\Shlink\Core\Util\RedirectResponseHelperInterface;
use Shlinkio\Shlink\Core\Visit\RequestTrackerInterface;

use function Laminas\Stratigility\middleware;
use function str_starts_with;

use const Shlinkio\Shlink\REDIRECT_URL_REQUEST_ATTRIBUTE;

class ExtraPathRedirectMiddlewareTest extends TestCase
{
    private MockObject & ShortUrlResolverInterface $resolver;
    private MockObject & RequestTrackerInterface $requestTracker;
    private MockObject & ShortUrlRedirectionBuilderInterface $redirectionBuilder;
    private MockObject & RedirectResponseHelperInterface $redirectResponseHelper;
    private MockObject & RequestHandlerInterface $handler;

    protected function setUp(): void
    {
        $this->resolver = $this->createMock(ShortUrlResolverInterface::class);
        $this->requestTracker = $this->createMock(RequestTrackerInterface::class);
        $this->redirectionBuilder = $this->createMock(ShortUrlRedirectionBuilderInterface::class);
        $this->redirectResponseHelper = $this->createMock(RedirectResponseHelperInterface::class);
        $this->handler = $this->createMock(RequestHandlerInterface::class);
        $this->handler->method('handle')->willReturn(new RedirectResponse(''));
    }

    #[Test, DataProvider('provideNonRedirectingRequests')]
    public function handlerIsCalledWhenConfigPreventsRedirectWithExtraPath(
        bool $appendExtraPath,
        bool $multiSegmentEnabled,
        ServerRequestInterface $request,
    ): void {
        $options = new UrlShortenerOptions(
            appendExtraPath: $appendExtraPath,
            multiSegmentSlugsEnabled: $multiSegmentEnabled,
        );
        $this->resolver->expects($this->never())->method('resolveEnabledShortUrl');
        $this->requestTracker->expects($this->never())->method('trackIfApplicable');
        $this->redirectionBuilder->expects($this->never())->method('buildShortUrlRedirect');
        $this->redirectResponseHelper->expects($this->never())->method('buildRedirectResponse');
        $this->handler->expects($this->once())->method('handle');

        $this->middleware($options)->process($request, $this->handler);
    }

    public static function provideNonRedirectingRequests(): iterable
    {
        $baseReq = ServerRequestFactory::fromGlobals();
        $buildReq = static fn (NotFoundType|null $type): ServerRequestInterface =>
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
                    middleware(function (): void {
                    }),
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

    #[Test, DataProvider('provideResolves')]
    public function handlerIsCalledWhenNoShortUrlIsFoundAfterExpectedAmountOfIterations(
        bool $multiSegmentEnabled,
        int $expectedResolveCalls,
    ): void {
        $options = new UrlShortenerOptions(appendExtraPath: true, multiSegmentSlugsEnabled: $multiSegmentEnabled);

        $type = $this->createMock(NotFoundType::class);
        $type->method('isRegularNotFound')->willReturn(true);
        $type->method('isInvalidShortUrl')->willReturn(true);
        $request = ServerRequestFactory::fromGlobals()->withAttribute(NotFoundType::class, $type)
                                                      ->withUri(new Uri('/shortCode/bar/baz'));

        $this->resolver->expects($this->exactly($expectedResolveCalls))->method('resolveEnabledShortUrl')->with(
            $this->callback(fn (ShortUrlIdentifier $id) => str_starts_with($id->shortCode, 'shortCode')),
        )->willThrowException(ShortUrlNotFoundException::fromNotFound(ShortUrlIdentifier::fromShortCodeAndDomain('')));
        $this->requestTracker->expects($this->never())->method('trackIfApplicable');
        $this->redirectionBuilder->expects($this->never())->method('buildShortUrlRedirect');
        $this->redirectResponseHelper->expects($this->never())->method('buildRedirectResponse');

        $this->middleware($options)->process($request, $this->handler);
    }

    #[Test, DataProvider('provideResolves')]
    public function visitIsTrackedAndRedirectIsReturnedWhenShortUrlIsFoundAfterExpectedAmountOfIterations(
        bool $multiSegmentEnabled,
        int $expectedResolveCalls,
        string|null $expectedExtraPath,
    ): void {
        $options = new UrlShortenerOptions(appendExtraPath: true, multiSegmentSlugsEnabled: $multiSegmentEnabled);

        $type = $this->createMock(NotFoundType::class);
        $type->method('isRegularNotFound')->willReturn(true);
        $type->method('isInvalidShortUrl')->willReturn(true);
        $request = ServerRequestFactory::fromGlobals()->withAttribute(NotFoundType::class, $type)
                                                      ->withUri(new Uri('https://s.test/shortCode/bar/baz'));
        $shortUrl = ShortUrl::withLongUrl('https://longUrl');

        $currentIteration = 1;
        $this->resolver->expects($this->exactly($expectedResolveCalls))->method('resolveEnabledShortUrl')->with(
            $this->callback(fn (ShortUrlIdentifier $id) => str_starts_with($id->shortCode, 'shortCode')),
        )->willReturnCallback(
            function () use ($shortUrl, &$currentIteration, $expectedResolveCalls): ShortUrl {
                if ($expectedResolveCalls === $currentIteration) {
                    return $shortUrl;
                }

                $currentIteration++;
                throw ShortUrlNotFoundException::fromNotFound(ShortUrlIdentifier::fromShortUrl($shortUrl));
            },
        );
        $this->redirectionBuilder->expects($this->once())->method('buildShortUrlRedirect')->with(
            $shortUrl,
            $this->isInstanceOf(ServerRequestInterface::class),
            $expectedExtraPath,
        )->willReturn('the_built_long_url');
        $this->redirectResponseHelper->expects($this->once())->method('buildRedirectResponse')->with(
            'the_built_long_url',
        )->willReturn(new RedirectResponse(''));
        $this->requestTracker->expects($this->once())->method('trackIfApplicable')->with(
            $shortUrl,
            $request->withAttribute(REDIRECT_URL_REQUEST_ATTRIBUTE, 'the_built_long_url'),
        );

        $this->middleware($options)->process($request, $this->handler);
    }

    public static function provideResolves(): iterable
    {
        yield [false, 1, '/bar/baz'];
        yield [true, 3, null];
    }

    private function middleware(UrlShortenerOptions|null $options = null): ExtraPathRedirectMiddleware
    {
        return new ExtraPathRedirectMiddleware(
            $this->resolver,
            $this->requestTracker,
            $this->redirectionBuilder,
            $this->redirectResponseHelper,
            $options ?? new UrlShortenerOptions(appendExtraPath: true),
        );
    }
}
