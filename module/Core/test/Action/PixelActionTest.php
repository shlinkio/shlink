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
        $this->urlResolver->expects($this->once())->method('resolveEnabledShortUrl')->with(
            ShortUrlIdentifier::fromShortCodeAndDomain($shortCode, ''),
        )->willReturn(ShortUrl::withLongUrl('http://domain.com/foo/bar'));
        $this->requestTracker->expects($this->once())->method('trackIfApplicable')->withAnyParameters();

        $request = (new ServerRequest())->withAttribute('shortCode', $shortCode);
        $response = $this->action->process($request, $this->createMock(RequestHandlerInterface::class));

        self::assertInstanceOf(PixelResponse::class, $response);
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('image/gif', $response->getHeaderLine('content-type'));
    }
}
