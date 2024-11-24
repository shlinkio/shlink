<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Action;

use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Common\Response\PixelResponse;
use Shlinkio\Shlink\Core\Action\PixelAction;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlResolverInterface;
use Shlinkio\Shlink\Core\Visit\RequestTrackerInterface;

use const Shlinkio\Shlink\REDIRECT_URL_REQUEST_ATTRIBUTE;

class PixelActionTest extends TestCase
{
    private PixelAction $action;
    private MockObject & ShortUrlResolverInterface $urlResolver;
    private MockObject & RequestTrackerInterface $requestTracker;

    protected function setUp(): void
    {
        $this->urlResolver = $this->createMock(ShortUrlResolverInterface::class);
        $this->requestTracker = $this->createMock(RequestTrackerInterface::class);

        $this->action = new PixelAction($this->urlResolver, $this->requestTracker);
    }

    #[Test]
    public function imageIsReturned(): void
    {
        $shortCode = 'abc123';
        $shortUrl = ShortUrl::withLongUrl('http://domain.com/foo/bar');
        $request = (new ServerRequest())->withAttribute('shortCode', $shortCode);

        $this->urlResolver->expects($this->once())->method('resolveEnabledShortUrl')->with(
            ShortUrlIdentifier::fromShortCodeAndDomain($shortCode, ''),
        )->willReturn($shortUrl);
        $this->requestTracker->expects($this->once())->method('trackIfApplicable')->with(
            $shortUrl,
            $request->withAttribute(REDIRECT_URL_REQUEST_ATTRIBUTE, null),
        );

        $response = $this->action->process($request, $this->createMock(RequestHandlerInterface::class));

        self::assertInstanceOf(PixelResponse::class, $response);
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('image/gif', $response->getHeaderLine('content-type'));
    }
}
