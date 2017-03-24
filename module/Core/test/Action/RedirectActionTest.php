<?php
namespace ShlinkioTest\Shlink\Core\Action;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Action\RedirectAction;
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

    public function setUp()
    {
        $this->urlShortener = $this->prophesize(UrlShortener::class);
        $visitTracker = $this->prophesize(VisitsTracker::class);
        $visitTracker->track(Argument::any());
        $this->action = new RedirectAction($this->urlShortener->reveal(), $visitTracker->reveal());
    }

    /**
     * @test
     */
    public function redirectionIsPerformedToLongUrl()
    {
        $shortCode = 'abc123';
        $expectedUrl = 'http://domain.com/foo/bar';
        $this->urlShortener->shortCodeToUrl($shortCode)->willReturn($expectedUrl)
                                                       ->shouldBeCalledTimes(1);

        $request = ServerRequestFactory::fromGlobals()->withAttribute('shortCode', $shortCode);
        $response = $this->action->process($request, TestUtils::createDelegateMock()->reveal());

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
        $this->urlShortener->shortCodeToUrl($shortCode)->willReturn(null)
                           ->shouldBeCalledTimes(1);
        $delegate = TestUtils::createDelegateMock();

        $request = ServerRequestFactory::fromGlobals()->withAttribute('shortCode', $shortCode);
        $this->action->process($request, $delegate->reveal());

        $delegate->process($request)->shouldHaveBeenCalledTimes(1);
    }

    /**
     * @test
     */
    public function nextMiddlewareIsInvokedIfAnExceptionIsThrown()
    {
        $shortCode = 'abc123';
        $this->urlShortener->shortCodeToUrl($shortCode)->willThrow(\Exception::class)
                                                       ->shouldBeCalledTimes(1);
        $delegate = TestUtils::createDelegateMock();

        $request = ServerRequestFactory::fromGlobals()->withAttribute('shortCode', $shortCode);
        $this->action->process($request, $delegate->reveal());

        $delegate->process($request)->shouldHaveBeenCalledTimes(1);
    }
}
