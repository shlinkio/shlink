<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Action;

use Interop\Http\ServerMiddleware\DelegateInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Common\Response\QrCodeResponse;
use Shlinkio\Shlink\Core\Action\QrCodeAction;
use Shlinkio\Shlink\Core\Exception\EntityDoesNotExistException;
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
    public function aNotFoundShortCodeWillDelegateIntoNextMiddleware()
    {
        $shortCode = 'abc123';
        $this->urlShortener->shortCodeToUrl($shortCode)->willThrow(EntityDoesNotExistException::class)
                                                       ->shouldBeCalledTimes(1);
        $delegate = $this->prophesize(DelegateInterface::class);
        $process = $delegate->process(Argument::any())->willReturn(new Response());

        $this->action->process(
            ServerRequestFactory::fromGlobals()->withAttribute('shortCode', $shortCode),
            $delegate->reveal()
        );

        $process->shouldHaveBeenCalledTimes(1);
    }

    /**
     * @test
     */
    public function anInvalidShortCodeWillReturnNotFoundResponse()
    {
        $shortCode = 'abc123';
        $this->urlShortener->shortCodeToUrl($shortCode)->willThrow(InvalidShortCodeException::class)
                                                       ->shouldBeCalledTimes(1);
        $delegate = $this->prophesize(DelegateInterface::class);
        /** @var MethodProphecy $process */
        $process = $delegate->process(Argument::any())->willReturn(new Response());

        $this->action->process(
            ServerRequestFactory::fromGlobals()->withAttribute('shortCode', $shortCode),
            $delegate->reveal()
        );

        $process->shouldHaveBeenCalledTimes(1);
    }

    /**
     * @test
     */
    public function aCorrectRequestReturnsTheQrCodeResponse()
    {
        $shortCode = 'abc123';
        $this->urlShortener->shortCodeToUrl($shortCode)->willReturn('')->shouldBeCalledTimes(1);
        $delegate = $this->prophesize(DelegateInterface::class);

        $resp = $this->action->process(
            ServerRequestFactory::fromGlobals()->withAttribute('shortCode', $shortCode),
            $delegate->reveal()
        );

        $this->assertInstanceOf(QrCodeResponse::class, $resp);
        $this->assertEquals(200, $resp->getStatusCode());
        $delegate->process(Argument::any())->shouldHaveBeenCalledTimes(0);
    }
}
