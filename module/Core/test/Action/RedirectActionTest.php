<?php
namespace ShlinkioTest\Shlink\Core\Action;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\Action\RedirectAction;
use Shlinkio\Shlink\Core\Service\UrlShortener;
use Shlinkio\Shlink\Core\Service\VisitsTracker;
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
        $response = $this->action->__invoke($request, new Response());

        $this->assertInstanceOf(Response\RedirectResponse::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('Location'));
        $this->assertEquals($expectedUrl, $response->getHeaderLine('Location'));
    }

    /**
     * @test
     */
    public function nextErrorMiddlewareIsInvokedIfLongUrlIsNotFound()
    {
        $shortCode = 'abc123';
        $this->urlShortener->shortCodeToUrl($shortCode)->willReturn(null)
                           ->shouldBeCalledTimes(1);

        $request = ServerRequestFactory::fromGlobals()->withAttribute('shortCode', $shortCode);
        $originalResponse = new Response();
        $test = $this;
        $this->action->__invoke($request, $originalResponse, function (
            ServerRequestInterface $req,
            ResponseInterface $resp,
            $error
        ) use (
            $test,
            $request
        ) {
            $test->assertSame($request, $req);
            $test->assertEquals(404, $resp->getStatusCode());
            $test->assertEquals('Not Found', $error);
        });
    }

    /**
     * @test
     */
    public function nextErrorMiddlewareIsInvokedIfAnExceptionIsThrown()
    {
        $shortCode = 'abc123';
        $this->urlShortener->shortCodeToUrl($shortCode)->willThrow(\Exception::class)
                                                       ->shouldBeCalledTimes(1);

        $request = ServerRequestFactory::fromGlobals()->withAttribute('shortCode', $shortCode);
        $originalResponse = new Response();
        $test = $this;
        $this->action->__invoke($request, $originalResponse, function (
            ServerRequestInterface $req,
            ResponseInterface $resp,
            $error
        ) use (
            $test,
            $request
        ) {
            $test->assertSame($request, $req);
            $test->assertEquals(404, $resp->getStatusCode());
            $test->assertEquals('Not Found', $error);
        });
    }
}
