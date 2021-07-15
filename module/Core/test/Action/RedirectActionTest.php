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
use Shlinkio\Shlink\Core\Service\ShortUrl\ShortUrlResolverInterface;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlRedirectionBuilderInterface;
use Shlinkio\Shlink\Core\Util\RedirectResponseHelperInterface;
use Shlinkio\Shlink\Core\Visit\RequestTrackerInterface;

class RedirectActionTest extends TestCase
{
    use ProphecyTrait;

    private const LONG_URL = 'https://domain.com/foo/bar?some=thing';

    private RedirectAction $action;
    private ObjectProphecy $urlResolver;
    private ObjectProphecy $requestTracker;
    private ObjectProphecy $redirectRespHelper;

    public function setUp(): void
    {
        $this->urlResolver = $this->prophesize(ShortUrlResolverInterface::class);
        $this->requestTracker = $this->prophesize(RequestTrackerInterface::class);
        $this->redirectRespHelper = $this->prophesize(RedirectResponseHelperInterface::class);

        $redirectBuilder = $this->prophesize(ShortUrlRedirectionBuilderInterface::class);
        $redirectBuilder->buildShortUrlRedirect(Argument::cetera())->willReturn(self::LONG_URL);

        $this->action = new RedirectAction(
            $this->urlResolver->reveal(),
            $this->requestTracker->reveal(),
            $redirectBuilder->reveal(),
            $this->redirectRespHelper->reveal(),
        );
    }

    /** @test */
    public function redirectionIsPerformedToLongUrl(): void
    {
        $shortCode = 'abc123';
        $shortUrl = ShortUrl::withLongUrl(self::LONG_URL);
        $shortCodeToUrl = $this->urlResolver->resolveEnabledShortUrl(
            new ShortUrlIdentifier($shortCode, ''),
        )->willReturn($shortUrl);
        $track = $this->requestTracker->trackIfApplicable(Argument::cetera())->will(function (): void {
        });
        $expectedResp = new Response\RedirectResponse(self::LONG_URL);
        $buildResp = $this->redirectRespHelper->buildRedirectResponse(self::LONG_URL)->willReturn($expectedResp);

        $request = (new ServerRequest())->withAttribute('shortCode', $shortCode);
        $response = $this->action->process($request, $this->prophesize(RequestHandlerInterface::class)->reveal());

        self::assertSame($expectedResp, $response);
        $buildResp->shouldHaveBeenCalledOnce();
        $shortCodeToUrl->shouldHaveBeenCalledOnce();
        $track->shouldHaveBeenCalledOnce();
    }

//    public function provideQueries(): iterable
//    {
//        yield [[]];
//        yield [['foobar' => 'notrack']];
//        yield [['foobar' => 'barfoo']];
//        yield [['foobar' => null]];
//    }

    /** @test */
    public function nextMiddlewareIsInvokedIfLongUrlIsNotFound(): void
    {
        $shortCode = 'abc123';
        $this->urlResolver->resolveEnabledShortUrl(new ShortUrlIdentifier($shortCode, ''))
            ->willThrow(ShortUrlNotFoundException::class)
            ->shouldBeCalledOnce();
        $this->requestTracker->trackIfApplicable(Argument::cetera())->shouldNotBeCalled();

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handle = $handler->handle(Argument::any())->willReturn(new Response());

        $request = (new ServerRequest())->withAttribute('shortCode', $shortCode);
        $this->action->process($request, $handler->reveal());

        $handle->shouldHaveBeenCalledOnce();
    }

//    /** @test */
//    public function trackingIsDisabledWhenRequestIsForwardedFromHead(): void
//    {
//        $shortCode = 'abc123';
//        $shortUrl = ShortUrl::withLongUrl(self::LONG_URL);
//        $this->urlResolver->resolveEnabledShortUrl(new ShortUrlIdentifier($shortCode, ''))->willReturn($shortUrl);
//        $track = $this->requestTracker->trackIfApplicable(Argument::cetera())->will(function (): void {
//        });
//        $buildResp = $this->redirectRespHelper->buildRedirectResponse(self::LONG_URL)->willReturn(
//            new Response\RedirectResponse(''),
//        );
//
//        $request = (new ServerRequest())->withAttribute('shortCode', $shortCode)
//                                        ->withAttribute(
//                                            ImplicitHeadMiddleware::FORWARDED_HTTP_METHOD_ATTRIBUTE,
//                                            RequestMethodInterface::METHOD_HEAD,
//                                        );
//        $this->action->process($request, $this->prophesize(RequestHandlerInterface::class)->reveal());
//
//        $buildResp->shouldHaveBeenCalled();
//        $track->shouldNotHaveBeenCalled();
//    }
}
