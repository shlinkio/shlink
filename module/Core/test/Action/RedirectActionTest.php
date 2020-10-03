<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Action;

use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Router\Middleware\ImplicitHeadMiddleware;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\Action\RedirectAction;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Options;
use Shlinkio\Shlink\Core\Service\ShortUrl\ShortUrlResolverInterface;
use Shlinkio\Shlink\Core\Service\VisitsTrackerInterface;

use function array_key_exists;

class RedirectActionTest extends TestCase
{
    private RedirectAction $action;
    private ObjectProphecy $urlResolver;
    private ObjectProphecy $visitTracker;
    private Options\UrlShortenerOptions $shortenerOpts;

    public function setUp(): void
    {
        $this->urlResolver = $this->prophesize(ShortUrlResolverInterface::class);
        $this->visitTracker = $this->prophesize(VisitsTrackerInterface::class);
        $this->shortenerOpts = new Options\UrlShortenerOptions();

        $this->action = new RedirectAction(
            $this->urlResolver->reveal(),
            $this->visitTracker->reveal(),
            new Options\AppOptions(['disableTrackParam' => 'foobar']),
            $this->shortenerOpts,
        );
    }

    /**
     * @test
     * @dataProvider provideQueries
     */
    public function redirectionIsPerformedToLongUrl(string $expectedUrl, array $query): void
    {
        $shortCode = 'abc123';
        $shortUrl = new ShortUrl('http://domain.com/foo/bar?some=thing');
        $shortCodeToUrl = $this->urlResolver->resolveEnabledShortUrl(
            new ShortUrlIdentifier($shortCode, ''),
        )->willReturn($shortUrl);
        $track = $this->visitTracker->track(Argument::cetera())->will(function (): void {
        });

        $request = (new ServerRequest())->withAttribute('shortCode', $shortCode)->withQueryParams($query);
        $response = $this->action->process($request, $this->prophesize(RequestHandlerInterface::class)->reveal());

        self::assertInstanceOf(Response\RedirectResponse::class, $response);
        self::assertEquals(302, $response->getStatusCode());
        self::assertTrue($response->hasHeader('Location'));
        self::assertEquals($expectedUrl, $response->getHeaderLine('Location'));
        $shortCodeToUrl->shouldHaveBeenCalledOnce();
        $track->shouldHaveBeenCalledTimes(array_key_exists('foobar', $query) ? 0 : 1);
    }

    public function provideQueries(): iterable
    {
        yield ['http://domain.com/foo/bar?some=thing', []];
        yield ['http://domain.com/foo/bar?some=thing', ['foobar' => 'notrack']];
        yield ['http://domain.com/foo/bar?some=thing&else', ['else' => null]];
        yield ['http://domain.com/foo/bar?some=thing&foo=bar', ['foo' => 'bar']];
        yield ['http://domain.com/foo/bar?some=overwritten&foo=bar', ['foo' => 'bar', 'some' => 'overwritten']];
        yield ['http://domain.com/foo/bar?some=overwritten', ['foobar' => 'notrack', 'some' => 'overwritten']];
    }

    /** @test */
    public function nextMiddlewareIsInvokedIfLongUrlIsNotFound(): void
    {
        $shortCode = 'abc123';
        $this->urlResolver->resolveEnabledShortUrl(new ShortUrlIdentifier($shortCode, ''))
            ->willThrow(ShortUrlNotFoundException::class)
            ->shouldBeCalledOnce();
        $this->visitTracker->track(Argument::cetera())->shouldNotBeCalled();

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handle = $handler->handle(Argument::any())->willReturn(new Response());

        $request = (new ServerRequest())->withAttribute('shortCode', $shortCode);
        $this->action->process($request, $handler->reveal());

        $handle->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function trackingIsDisabledWhenRequestIsForwardedFromHead(): void
    {
        $shortCode = 'abc123';
        $shortUrl = new ShortUrl('http://domain.com/foo/bar?some=thing');
        $this->urlResolver->resolveEnabledShortUrl(new ShortUrlIdentifier($shortCode, ''))->willReturn($shortUrl);
        $track = $this->visitTracker->track(Argument::cetera())->will(function (): void {
        });

        $request = (new ServerRequest())->withAttribute('shortCode', $shortCode)
                                        ->withAttribute(
                                            ImplicitHeadMiddleware::FORWARDED_HTTP_METHOD_ATTRIBUTE,
                                            RequestMethodInterface::METHOD_HEAD,
                                        );
        $this->action->process($request, $this->prophesize(RequestHandlerInterface::class)->reveal());

        $track->shouldNotHaveBeenCalled();
    }

    /**
     * @test
     * @dataProvider provideRedirectConfigs
     */
    public function expectedStatusCodeAndCacheIsReturnedBasedOnConfig(
        int $configuredStatus,
        int $configuredLifetime,
        int $expectedStatus,
        ?string $expectedCacheControl
    ): void {
        $this->shortenerOpts->redirectStatusCode = $configuredStatus;
        $this->shortenerOpts->redirectCacheLifetime = $configuredLifetime;

        $shortUrl = new ShortUrl('http://domain.com/foo/bar');
        $shortCode = $shortUrl->getShortCode();
        $this->urlResolver->resolveEnabledShortUrl(Argument::cetera())->willReturn($shortUrl);

        $request = (new ServerRequest())->withAttribute('shortCode', $shortCode);
        $response = $this->action->process($request, $this->prophesize(RequestHandlerInterface::class)->reveal());

        self::assertInstanceOf(Response\RedirectResponse::class, $response);
        self::assertEquals($expectedStatus, $response->getStatusCode());
        self::assertEquals($response->hasHeader('Cache-Control'), $expectedCacheControl !== null);
        self::assertEquals($response->getHeaderLine('Cache-Control'), $expectedCacheControl ?? '');
    }

    public function provideRedirectConfigs(): iterable
    {
        yield 'status 302' => [302, 20, 302, null];
        yield 'status over 302' => [400, 20, 302, null];
        yield 'status below 301' => [201, 20, 302, null];
        yield 'status 301 with valid expiration' => [301, 20, 301, 'private,max-age=20'];
        yield 'status 301 with zero expiration' => [301, 0, 301, 'private,max-age=30'];
        yield 'status 301 with negative expiration' => [301, -20, 301, 'private,max-age=30'];
    }
}
