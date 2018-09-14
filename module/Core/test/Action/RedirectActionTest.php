<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Action;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\Action\RedirectAction;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\EntityDoesNotExistException;
use Shlinkio\Shlink\Core\Options\AppOptions;
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
    protected $action;
    /**
     * @var ObjectProphecy
     */
    protected $urlShortener;
    /**
     * @var ObjectProphecy
     */
    protected $visitTracker;

    public function setUp()
    {
        $this->urlShortener = $this->prophesize(UrlShortener::class);
        $this->visitTracker = $this->prophesize(VisitsTracker::class);

        $this->action = new RedirectAction(
            $this->urlShortener->reveal(),
            $this->visitTracker->reveal(),
            new AppOptions(['disableTrackParam' => 'foobar'])
        );
    }

    /**
     * @test
     */
    public function redirectionIsPerformedToLongUrl()
    {
        $shortCode = 'abc123';
        $expectedUrl = 'http://domain.com/foo/bar';
        $shortUrl = (new ShortUrl())->setLongUrl($expectedUrl);
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

        $delegate = $this->prophesize(RequestHandlerInterface::class);
        /** @var MethodProphecy $process */
        $process = $delegate->handle(Argument::any())->willReturn(new Response());

        $request = ServerRequestFactory::fromGlobals()->withAttribute('shortCode', $shortCode);
        $this->action->process($request, $delegate->reveal());

        $process->shouldHaveBeenCalledTimes(1);
    }

    /**
     * @test
     */
    public function visitIsNotTrackedIfDisableParamIsProvided()
    {
        $shortCode = 'abc123';
        $expectedUrl = 'http://domain.com/foo/bar';
        $shortUrl = (new ShortUrl())->setLongUrl($expectedUrl);
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
