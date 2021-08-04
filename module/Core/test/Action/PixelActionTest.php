<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Action;

use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Common\Response\PixelResponse;
use Shlinkio\Shlink\Core\Action\PixelAction;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Service\ShortUrl\ShortUrlResolverInterface;
use Shlinkio\Shlink\Core\Visit\RequestTrackerInterface;

class PixelActionTest extends TestCase
{
    use ProphecyTrait;

    private PixelAction $action;
    private ObjectProphecy $urlResolver;
    private ObjectProphecy $requestTracker;

    public function setUp(): void
    {
        $this->urlResolver = $this->prophesize(ShortUrlResolverInterface::class);
        $this->requestTracker = $this->prophesize(RequestTrackerInterface::class);

        $this->action = new PixelAction($this->urlResolver->reveal(), $this->requestTracker->reveal());
    }

    /** @test */
    public function imageIsReturned(): void
    {
        $shortCode = 'abc123';
        $this->urlResolver->resolveEnabledShortUrl(new ShortUrlIdentifier($shortCode, ''))->willReturn(
            ShortUrl::withLongUrl('http://domain.com/foo/bar'),
        )->shouldBeCalledOnce();
        $this->requestTracker->trackIfApplicable(Argument::cetera())->shouldBeCalledOnce();

        $request = (new ServerRequest())->withAttribute('shortCode', $shortCode);
        $response = $this->action->process($request, $this->prophesize(RequestHandlerInterface::class)->reveal());

        self::assertInstanceOf(PixelResponse::class, $response);
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('image/gif', $response->getHeaderLine('content-type'));
    }
}
