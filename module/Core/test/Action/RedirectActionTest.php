<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Action;

use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Router\Middleware\ImplicitHeadMiddleware;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\Action\RedirectAction;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Options;
use Shlinkio\Shlink\Core\Service\ShortUrl\ShortUrlResolverInterface;
use Shlinkio\Shlink\Core\Service\VisitsTrackerInterface;
use Shlinkio\Shlink\Core\Util\RedirectResponseHelperInterface;

use function array_key_exists;

class RedirectActionTest extends TestCase
{
    use ProphecyTrait;

    private RedirectAction $action;
    private ObjectProphecy $urlResolver;
    private ObjectProphecy $visitTracker;
    private ObjectProphecy $redirectRespHelper;

    public function setUp(): void
    {
        $this->urlResolver = $this->prophesize(ShortUrlResolverInterface::class);
        $this->visitTracker = $this->prophesize(VisitsTrackerInterface::class);
        $this->redirectRespHelper = $this->prophesize(RedirectResponseHelperInterface::class);

        $this->action = new RedirectAction(
            $this->urlResolver->reveal(),
            $this->visitTracker->reveal(),
            new Options\AppOptions(['disableTrackParam' => 'foobar']),
            $this->redirectRespHelper->reveal(),
        );
    }

    /**
     * @test
     * @dataProvider provideQueries
     */
    public function redirectionIsPerformedToLongUrl(string $expectedUrl, array $query): void
    {
        $shortCode = 'abc123';
        $shortUrl = ShortUrl::withLongUrl('http://domain.com/foo/bar?some=thing');
        $shortCodeToUrl = $this->urlResolver->resolveEnabledShortUrl(
            new ShortUrlIdentifier($shortCode, ''),
        )->willReturn($shortUrl);
        $track = $this->visitTracker->track(Argument::cetera())->will(function (): void {
        });
        $expectedResp = new Response\RedirectResponse($expectedUrl);
        $buildResp = $this->redirectRespHelper->buildRedirectResponse($expectedUrl)->willReturn($expectedResp);

        $request = (new ServerRequest())->withAttribute('shortCode', $shortCode)->withQueryParams($query);
        $response = $this->action->process($request, $this->prophesize(RequestHandlerInterface::class)->reveal());

        self::assertSame($expectedResp, $response);
        $buildResp->shouldHaveBeenCalledOnce();
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
        $shortUrl = ShortUrl::withLongUrl('http://domain.com/foo/bar?some=thing');
        $this->urlResolver->resolveEnabledShortUrl(new ShortUrlIdentifier($shortCode, ''))->willReturn($shortUrl);
        $track = $this->visitTracker->track(Argument::cetera())->will(function (): void {
        });
        $buildResp = $this->redirectRespHelper->buildRedirectResponse(
            'http://domain.com/foo/bar?some=thing',
        )->willReturn(new Response\RedirectResponse(''));

        $request = (new ServerRequest())->withAttribute('shortCode', $shortCode)
                                        ->withAttribute(
                                            ImplicitHeadMiddleware::FORWARDED_HTTP_METHOD_ATTRIBUTE,
                                            RequestMethodInterface::METHOD_HEAD,
                                        );
        $this->action->process($request, $this->prophesize(RequestHandlerInterface::class)->reveal());

        $buildResp->shouldHaveBeenCalled();
        $track->shouldNotHaveBeenCalled();
    }
}
