<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Action;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\Action\RedirectAction;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Options;
use Shlinkio\Shlink\Core\Service\UrlShortener;
use Shlinkio\Shlink\Core\Service\VisitsTracker;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;

use function array_key_exists;

class RedirectActionTest extends TestCase
{
    private RedirectAction $action;
    private ObjectProphecy $urlShortener;
    private ObjectProphecy $visitTracker;

    public function setUp(): void
    {
        $this->urlShortener = $this->prophesize(UrlShortener::class);
        $this->visitTracker = $this->prophesize(VisitsTracker::class);

        $this->action = new RedirectAction(
            $this->urlShortener->reveal(),
            $this->visitTracker->reveal(),
            new Options\AppOptions(['disableTrackParam' => 'foobar'])
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
        $shortCodeToUrl = $this->urlShortener->shortCodeToUrl($shortCode, '')->willReturn($shortUrl);
        $track = $this->visitTracker->track(Argument::cetera())->will(function () {
        });

        $request = (new ServerRequest())->withAttribute('shortCode', $shortCode)->withQueryParams($query);
        $response = $this->action->process($request, $this->prophesize(RequestHandlerInterface::class)->reveal());

        $this->assertInstanceOf(Response\RedirectResponse::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('Location'));
        $this->assertEquals($expectedUrl, $response->getHeaderLine('Location'));
        $shortCodeToUrl->shouldHaveBeenCalledOnce();
        $track->shouldHaveBeenCalledTimes(array_key_exists('foobar', $query) ? 0 : 1);
    }

    public function provideQueries(): iterable
    {
        yield ['http://domain.com/foo/bar?some=thing', []];
        yield ['http://domain.com/foo/bar?some=thing', ['foobar' => 'notrack']];
        yield ['http://domain.com/foo/bar?some=thing&foo=bar', ['foo' => 'bar']];
        yield ['http://domain.com/foo/bar?some=overwritten&foo=bar', ['foo' => 'bar', 'some' => 'overwritten']];
        yield ['http://domain.com/foo/bar?some=overwritten', ['foobar' => 'notrack', 'some' => 'overwritten']];
    }

    /** @test */
    public function nextMiddlewareIsInvokedIfLongUrlIsNotFound(): void
    {
        $shortCode = 'abc123';
        $this->urlShortener->shortCodeToUrl($shortCode, '')->willThrow(ShortUrlNotFoundException::class)
                                                           ->shouldBeCalledOnce();
        $this->visitTracker->track(Argument::cetera())->shouldNotBeCalled();

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handle = $handler->handle(Argument::any())->willReturn(new Response());

        $request = (new ServerRequest())->withAttribute('shortCode', $shortCode);
        $this->action->process($request, $handler->reveal());

        $handle->shouldHaveBeenCalledOnce();
    }
}
