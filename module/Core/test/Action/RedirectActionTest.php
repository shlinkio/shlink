<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Action;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\Action\RedirectAction;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlRedirectionBuilderInterface;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlResolverInterface;
use Shlinkio\Shlink\Core\Util\RedirectResponseHelperInterface;
use Shlinkio\Shlink\Core\Visit\RequestTrackerInterface;

class RedirectActionTest extends TestCase
{
    private const LONG_URL = 'https://domain.com/foo/bar?some=thing';

    private RedirectAction $action;
    private MockObject & ShortUrlResolverInterface $urlResolver;
    private MockObject & RequestTrackerInterface $requestTracker;
    private MockObject & RedirectResponseHelperInterface $redirectRespHelper;

    protected function setUp(): void
    {
        $this->urlResolver = $this->createMock(ShortUrlResolverInterface::class);
        $this->requestTracker = $this->createMock(RequestTrackerInterface::class);
        $this->redirectRespHelper = $this->createMock(RedirectResponseHelperInterface::class);

        $redirectBuilder = $this->createMock(ShortUrlRedirectionBuilderInterface::class);
        $redirectBuilder->method('buildShortUrlRedirect')->withAnyParameters()->willReturn(self::LONG_URL);

        $this->action = new RedirectAction(
            $this->urlResolver,
            $this->requestTracker,
            $redirectBuilder,
            $this->redirectRespHelper,
        );
    }

    /** @test */
    public function redirectionIsPerformedToLongUrl(): void
    {
        $shortCode = 'abc123';
        $shortUrl = ShortUrl::withLongUrl(self::LONG_URL);
        $this->urlResolver->expects($this->once())->method('resolveEnabledShortUrl')->with(
            ShortUrlIdentifier::fromShortCodeAndDomain($shortCode, ''),
        )->willReturn($shortUrl);
        $this->requestTracker->expects($this->once())->method('trackIfApplicable');
        $expectedResp = new Response\RedirectResponse(self::LONG_URL);
        $this->redirectRespHelper->expects($this->once())->method('buildRedirectResponse')->with(
            self::LONG_URL,
        )->willReturn($expectedResp);

        $request = (new ServerRequest())->withAttribute('shortCode', $shortCode);
        $response = $this->action->process($request, $this->createMock(RequestHandlerInterface::class));

        self::assertSame($expectedResp, $response);
    }

    /** @test */
    public function nextMiddlewareIsInvokedIfLongUrlIsNotFound(): void
    {
        $shortCode = 'abc123';
        $this->urlResolver->expects($this->once())->method('resolveEnabledShortUrl')->with(
            ShortUrlIdentifier::fromShortCodeAndDomain($shortCode, ''),
        )->willThrowException(ShortUrlNotFoundException::fromNotFound(ShortUrlIdentifier::fromShortCodeAndDomain('')));
        $this->requestTracker->expects($this->never())->method('trackIfApplicable');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->withAnyParameters()->willReturn(new Response());

        $request = (new ServerRequest())->withAttribute('shortCode', $shortCode);
        $this->action->process($request, $handler);
    }
}
