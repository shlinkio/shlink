<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Action;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\Action\RedirectAction;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\EntityDoesNotExistException;
use Shlinkio\Shlink\Core\Options;
use Shlinkio\Shlink\Core\Service\UrlShortener;
use Shlinkio\Shlink\Core\Service\VisitsTracker;
use ShlinkioTest\Shlink\Common\Util\TestUtils;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;

class RedirectActionTest extends TestCase
{
    /**
     * @var RedirectAction
     */
    private $action;
    /**
     * @var ObjectProphecy
     */
    private $urlShortener;
    /**
     * @var ObjectProphecy
     */
    private $visitTracker;
    /**
     * @var Options\NotFoundShortUrlOptions
     */
    private $notFoundOptions;

    public function setUp()
    {
        $this->urlShortener = $this->prophesize(UrlShortener::class);
        $this->visitTracker = $this->prophesize(VisitsTracker::class);
        $this->notFoundOptions = new Options\NotFoundShortUrlOptions();

        $this->action = new RedirectAction(
            $this->urlShortener->reveal(),
            $this->visitTracker->reveal(),
            new Options\AppOptions(['disableTrackParam' => 'foobar']),
            $this->notFoundOptions
        );
    }

    /**
     * @test
     */
    public function redirectionIsPerformedToLongUrl()
    {
        $shortCode = 'abc123';
        $expectedUrl = 'http://domain.com/foo/bar';
        $shortUrl = new ShortUrl($expectedUrl);
        $this->urlShortener->shortCodeToUrl($shortCode)->willReturn($shortUrl)
                                                       ->shouldBeCalledTimes(1);
        $this->visitTracker->track(Argument::cetera())->shouldBeCalledTimes(1);

        $request = ServerRequestFactory::fromGlobals()->withAttribute('shortCode', $shortCode);
        $response = $this->action->process($request, TestUtils::createReqHandlerMock()->reveal());

        $this->assertInstanceOf(Response\RedirectResponse::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('Location'));
        $this->assertEquals($expectedUrl, $response->getHeaderLine('Location'));
    }

    /**
     * @test
     */
    public function nextMiddlewareIsInvokedIfLongUrlIsNotFound()
    {
        $shortCode = 'abc123';
        $this->urlShortener->shortCodeToUrl($shortCode)->willThrow(EntityDoesNotExistException::class)
                                                       ->shouldBeCalledTimes(1);
        $this->visitTracker->track(Argument::cetera())->shouldNotBeCalled();

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handle = $handler->handle(Argument::any())->willReturn(new Response());

        $request = ServerRequestFactory::fromGlobals()->withAttribute('shortCode', $shortCode);
        $this->action->process($request, $handler->reveal());

        $handle->shouldHaveBeenCalledTimes(1);
    }

    /**
     * @test
     */
    public function redirectToCustomUrlIsReturnedIfConfiguredSoAndShortUrlIsNotFound()
    {
        $shortCode = 'abc123';
        $shortCodeToUrl = $this->urlShortener->shortCodeToUrl($shortCode)->willThrow(
            EntityDoesNotExistException::class
        );

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handle = $handler->handle(Argument::any())->willReturn(new Response());

        $this->notFoundOptions->enableRedirection = true;
        $this->notFoundOptions->redirectTo = 'https://shlink.io';

        $request = ServerRequestFactory::fromGlobals()->withAttribute('shortCode', $shortCode);
        $resp = $this->action->process($request, $handler->reveal());

        $this->assertEquals(302, $resp->getStatusCode());
        $this->assertEquals('https://shlink.io', $resp->getHeaderLine('Location'));
        $shortCodeToUrl->shouldHaveBeenCalledTimes(1);
        $handle->shouldNotHaveBeenCalled();
    }

    /**
     * @test
     */
    public function visitIsNotTrackedIfDisableParamIsProvided()
    {
        $shortCode = 'abc123';
        $expectedUrl = 'http://domain.com/foo/bar';
        $shortUrl = new ShortUrl($expectedUrl);
        $this->urlShortener->shortCodeToUrl($shortCode)->willReturn($shortUrl)
                                                       ->shouldBeCalledTimes(1);
        $this->visitTracker->track(Argument::cetera())->shouldNotBeCalled();

        $request = ServerRequestFactory::fromGlobals()->withAttribute('shortCode', $shortCode)
                                                      ->withQueryParams(['foobar' => true]);
        $response = $this->action->process($request, TestUtils::createReqHandlerMock()->reveal());

        $this->assertInstanceOf(Response\RedirectResponse::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('Location'));
        $this->assertEquals($expectedUrl, $response->getHeaderLine('Location'));
    }
}
