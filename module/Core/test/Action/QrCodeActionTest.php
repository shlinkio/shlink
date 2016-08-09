<?php
namespace ShlinkioTest\Shlink\Core\Action;

use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Common\Response\QrCodeResponse;
use Shlinkio\Shlink\Core\Action\QrCodeAction;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\InvalidShortCodeException;
use Shlinkio\Shlink\Core\Service\UrlShortener;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Expressive\Router\RouterInterface;

class QrCodeActionTest extends TestCase
{
    /**
     * @var QrCodeAction
     */
    protected $action;
    /**
     * @var ObjectProphecy
     */
    protected $urlShortener;

    public function setUp()
    {
        $router = $this->prophesize(RouterInterface::class);
        $router->generateUri(Argument::cetera())->willReturn('/foo/bar');

        $this->urlShortener = $this->prophesize(UrlShortener::class);

        $this->action = new QrCodeAction($router->reveal(), $this->urlShortener->reveal());
    }

    /**
     * @test
     */
    public function aNonexistentShortCodeWillReturnNotFoundResponse()
    {
        $shortCode = 'abc123';
        $this->urlShortener->shortCodeToUrl($shortCode)->willReturn(null)->shouldBeCalledTimes(1);

        $resp = $this->action->__invoke(
            ServerRequestFactory::fromGlobals()->withAttribute('shortCode', $shortCode),
            new Response(),
            function ($req, $resp) {
                return $resp;
            }
        );
        $this->assertEquals(404, $resp->getStatusCode());
    }

    /**
     * @test
     */
    public function anInvalidShortCodeWillReturnNotFoundResponse()
    {
        $shortCode = 'abc123';
        $this->urlShortener->shortCodeToUrl($shortCode)->willThrow(InvalidShortCodeException::class)
                                                       ->shouldBeCalledTimes(1);

        $resp = $this->action->__invoke(
            ServerRequestFactory::fromGlobals()->withAttribute('shortCode', $shortCode),
            new Response(),
            function ($req, $resp) {
                return $resp;
            }
        );
        $this->assertEquals(404, $resp->getStatusCode());
    }

    /**
     * @test
     */
    public function aCorrectRequestReturnsTheQrCodeResponse()
    {
        $shortCode = 'abc123';
        $this->urlShortener->shortCodeToUrl($shortCode)->willReturn(new ShortUrl())->shouldBeCalledTimes(1);

        $resp = $this->action->__invoke(
            ServerRequestFactory::fromGlobals()->withAttribute('shortCode', $shortCode),
            new Response(),
            function ($req, $resp) {
                return $resp;
            }
        );

        $this->assertInstanceOf(QrCodeResponse::class, $resp);
        $this->assertEquals(200, $resp->getStatusCode());
    }
}
