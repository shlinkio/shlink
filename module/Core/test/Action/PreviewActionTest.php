<?php
namespace ShlinkioTest\Shlink\Core\Action;

use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Common\Exception\PreviewGenerationException;
use Shlinkio\Shlink\Common\Service\PreviewGenerator;
use Shlinkio\Shlink\Core\Action\PreviewAction;
use Shlinkio\Shlink\Core\Exception\InvalidShortCodeException;
use Shlinkio\Shlink\Core\Service\UrlShortener;
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
    public function invalidShortCodeFallbacksToNextMiddlewareWithStatusNotFound()
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
    public function correctShortCodeReturnsImageResponse()
    {
        $shortCode = 'abc123';
        $url = 'foobar.com';
        $path = __FILE__;
        $this->urlShortener->shortCodeToUrl($shortCode)->willReturn($url)->shouldBeCalledTimes(1);
        $this->previewGenerator->generatePreview($url)->willReturn($path)->shouldBeCalledTimes(1);

        $resp = $this->action->__invoke(
            ServerRequestFactory::fromGlobals()->withAttribute('shortCode', $shortCode),
            new Response()
        );

        $this->assertEquals(filesize($path), $resp->getHeaderLine('Content-length'));
        $this->assertEquals((new \finfo(FILEINFO_MIME))->file($path), $resp->getHeaderLine('Content-type'));
    }

    /**
     * @test
     */
    public function invalidShortcodeExceptionReturnsNotFound()
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
    public function previewExceptionReturnsNotFound()
    {
        $shortCode = 'abc123';
        $this->urlShortener->shortCodeToUrl($shortCode)->willThrow(PreviewGenerationException::class)
                                                       ->shouldBeCalledTimes(1);

        $resp = $this->action->__invoke(
            ServerRequestFactory::fromGlobals()->withAttribute('shortCode', $shortCode),
            new Response(),
            function ($req, $resp) {
                return $resp;
            }
        );

        $this->assertEquals(500, $resp->getStatusCode());
    }
}
