<?php
namespace ShlinkioTest\Shlink\Core\Action;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Common\Exception\PreviewGenerationException;
use Shlinkio\Shlink\Common\Service\PreviewGenerator;
use Shlinkio\Shlink\Core\Action\PreviewAction;
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
        $this->urlShortener->shortCodeToUrl($shortCode)->willReturn(null)->shouldBeCalledTimes(1);
        $delegate = TestUtils::createDelegateMock();

        $this->action->process(
            ServerRequestFactory::fromGlobals()->withAttribute('shortCode', $shortCode),
            $delegate->reveal()
        );

        $delegate->process(Argument::cetera())->shouldHaveBeenCalledTimes(1);
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
            TestUtils::createDelegateMock()->reveal()
        );

        $this->assertEquals(filesize($path), $resp->getHeaderLine('Content-length'));
        $this->assertEquals((new \finfo(FILEINFO_MIME))->file($path), $resp->getHeaderLine('Content-type'));
    }

    /**
     * @test
     */
    public function invalidShortcodeExceptionFallsBackToNextMiddleware()
    {
        $shortCode = 'abc123';
        $this->urlShortener->shortCodeToUrl($shortCode)->willThrow(InvalidShortCodeException::class)
                                                       ->shouldBeCalledTimes(1);
        $delegate = TestUtils::createDelegateMock();

        $this->action->process(
            ServerRequestFactory::fromGlobals()->withAttribute('shortCode', $shortCode),
            $delegate->reveal()
        );

        $delegate->process(Argument::any())->shouldHaveBeenCalledTimes(1);
    }
}
