<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Action;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Common\Response\PixelResponse;
use Shlinkio\Shlink\Core\Action\PixelAction;
use Shlinkio\Shlink\Core\Action\RedirectAction;
use Shlinkio\Shlink\Core\Options\AppOptions;
use Shlinkio\Shlink\Core\Service\UrlShortener;
use Shlinkio\Shlink\Core\Service\VisitsTracker;
use ShlinkioTest\Shlink\Common\Util\TestUtils;
use Zend\Diactoros\ServerRequestFactory;

class PixelActionTest extends TestCase
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

        $this->action = new PixelAction(
            $this->urlShortener->reveal(),
            $this->visitTracker->reveal(),
            new AppOptions()
        );
    }

    /**
     * @test
     */
    public function imageIsReturned()
    {
        $shortCode = 'abc123';
        $this->urlShortener->shortCodeToUrl($shortCode)->willReturn('http://domain.com/foo/bar')
                                                       ->shouldBeCalledTimes(1);
        $this->visitTracker->track(Argument::cetera())->willReturn(null)
                                                      ->shouldBeCalledTimes(1);

        $request = ServerRequestFactory::fromGlobals()->withAttribute('shortCode', $shortCode);
        $response = $this->action->process($request, TestUtils::createReqHandlerMock()->reveal());

        $this->assertInstanceOf(PixelResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('image/gif', $response->getHeaderLine('content-type'));
    }
}
