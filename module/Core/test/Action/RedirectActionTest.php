<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Action;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
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

use const Shlinkio\Shlink\REDIRECT_URL_REQUEST_ATTRIBUTE;

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

    #[Test]
    public function redirectionIsPerformedToLongUrl(): void
    {
        $shortCode = 'abc123';
        $shortUrl = ShortUrl::withLongUrl(self::LONG_URL);
        $expectedResp = new Response\RedirectResponse(self::LONG_URL);
        $request = (new ServerRequest())->withAttribute('shortCode', $shortCode);

        $this->urlResolver->expects($this->once())->method('resolveEnabledShortUrl')->with(
            ShortUrlIdentifier::fromShortCodeAndDomain($shortCode, ''),
        )->willReturn($shortUrl);
        $this->requestTracker->expects($this->once())->method('trackIfApplicable')->with(
            $shortUrl,
            $request->withAttribute(REDIRECT_URL_REQUEST_ATTRIBUTE, self::LONG_URL),
        );
        $this->redirectRespHelper->expects($this->once())->method('buildRedirectResponse')->with(
            self::LONG_URL,
        )->willReturn($expectedResp);

        $response = $this->action->process($request, $this->createMock(RequestHandlerInterface::class));

        self::assertSame($expectedResp, $response);
    }

    #[Test]
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
