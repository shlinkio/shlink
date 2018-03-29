<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Action;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Common\Service\PreviewGenerator;
use Shlinkio\Shlink\Core\Action\PreviewAction;
use Shlinkio\Shlink\Core\Exception\EntityDoesNotExistException;
use Shlinkio\Shlink\Core\Exception\InvalidShortCodeException;
use Shlinkio\Shlink\Core\Service\UrlShortener;
use ShlinkioTest\Shlink\Common\Util\TestUtils;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;

class PreviewActionTest extends TestCase
{
    /**
     * @var PreviewAction
     */
    protected $action;
    /**
     * @var ObjectProphecy
     */
    private $previewGenerator;
    /**
     * @var ObjectProphecy
     */
    private $urlShortener;

    public function setUp()
    {
        $this->previewGenerator = $this->prophesize(PreviewGenerator::class);
        $this->urlShortener = $this->prophesize(UrlShortener::class);
        $this->action = new PreviewAction($this->previewGenerator->reveal(), $this->urlShortener->reveal());
    }

    /**
     * @test
     */
    public function invalidShortCodeFallsBackToNextMiddleware()
    {
        $shortCode = 'abc123';
        $this->urlShortener->shortCodeToUrl($shortCode)->willThrow(EntityDoesNotExistException::class)
                                                       ->shouldBeCalledTimes(1);
        $delegate = $this->prophesize(RequestHandlerInterface::class);
        $delegate->handle(Argument::cetera())->shouldBeCalledTimes(1)
                                              ->willReturn(new Response());

        $this->action->process(
            ServerRequestFactory::fromGlobals()->withAttribute('shortCode', $shortCode),
            $delegate->reveal()
        );
    }

    /**
     * @test
     */
    public function correctShortCodeReturnsImageResponse()
    {
        $shortCode = 'abc123';
        $url = 'foobar.com';
        $path = __FILE__;
        $this->urlShortener->shortCodeToUrl($shortCode)->willReturn($url)->shouldBeCalledTimes(1);
        $this->previewGenerator->generatePreview($url)->willReturn($path)->shouldBeCalledTimes(1);

        $resp = $this->action->process(
            ServerRequestFactory::fromGlobals()->withAttribute('shortCode', $shortCode),
            TestUtils::createReqHandlerMock()->reveal()
        );

        $this->assertEquals(filesize($path), $resp->getHeaderLine('Content-length'));
        $this->assertEquals((new \finfo(FILEINFO_MIME))->file($path), $resp->getHeaderLine('Content-type'));
    }

    /**
     * @test
     */
    public function invalidShortCodeExceptionFallsBackToNextMiddleware()
    {
        $shortCode = 'abc123';
        $this->urlShortener->shortCodeToUrl($shortCode)->willThrow(InvalidShortCodeException::class)
                                                       ->shouldBeCalledTimes(1);
        $delegate = $this->prophesize(RequestHandlerInterface::class);
        /** @var MethodProphecy $process */
        $process = $delegate->handle(Argument::any())->willReturn(new Response());

        $this->action->process(
            ServerRequestFactory::fromGlobals()->withAttribute('shortCode', $shortCode),
            $delegate->reveal()
        );

        $process->shouldHaveBeenCalledTimes(1);
    }
}
