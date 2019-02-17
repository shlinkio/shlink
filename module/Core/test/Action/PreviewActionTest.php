<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Action;

use finfo;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Common\Service\PreviewGenerator;
use Shlinkio\Shlink\Core\Action\PreviewAction;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\EntityDoesNotExistException;
use Shlinkio\Shlink\Core\Exception\InvalidShortCodeException;
use Shlinkio\Shlink\Core\Service\UrlShortener;
use ShlinkioTest\Shlink\Common\Util\TestUtils;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use const FILEINFO_MIME;
use function filesize;

class PreviewActionTest extends TestCase
{
    /** @var PreviewAction */
    private $action;
    /** @var ObjectProphecy */
    private $previewGenerator;
    /** @var ObjectProphecy */
    private $urlShortener;

    public function setUp(): void
    {
        $this->previewGenerator = $this->prophesize(PreviewGenerator::class);
        $this->urlShortener = $this->prophesize(UrlShortener::class);
        $this->action = new PreviewAction($this->previewGenerator->reveal(), $this->urlShortener->reveal());
    }

    /** @test */
    public function invalidShortCodeFallsBackToNextMiddleware()
    {
        $shortCode = 'abc123';
        $this->urlShortener->shortCodeToUrl($shortCode)->willThrow(EntityDoesNotExistException::class)
                                                       ->shouldBeCalledOnce();
        $delegate = $this->prophesize(RequestHandlerInterface::class);
        $delegate->handle(Argument::cetera())->shouldBeCalledOnce()
                                              ->willReturn(new Response());

        $this->action->process((new ServerRequest())->withAttribute('shortCode', $shortCode), $delegate->reveal());
    }

    /** @test */
    public function correctShortCodeReturnsImageResponse()
    {
        $shortCode = 'abc123';
        $url = 'foobar.com';
        $shortUrl = new ShortUrl($url);
        $path = __FILE__;
        $this->urlShortener->shortCodeToUrl($shortCode)->willReturn($shortUrl)->shouldBeCalledOnce();
        $this->previewGenerator->generatePreview($url)->willReturn($path)->shouldBeCalledOnce();

        $resp = $this->action->process(
            (new ServerRequest())->withAttribute('shortCode', $shortCode),
            TestUtils::createReqHandlerMock()->reveal()
        );

        $this->assertEquals(filesize($path), $resp->getHeaderLine('Content-length'));
        $this->assertEquals((new finfo(FILEINFO_MIME))->file($path), $resp->getHeaderLine('Content-type'));
    }

    /** @test */
    public function invalidShortCodeExceptionFallsBackToNextMiddleware()
    {
        $shortCode = 'abc123';
        $this->urlShortener->shortCodeToUrl($shortCode)->willThrow(InvalidShortCodeException::class)
                                                       ->shouldBeCalledOnce();
        $delegate = $this->prophesize(RequestHandlerInterface::class);
        /** @var MethodProphecy $process */
        $process = $delegate->handle(Argument::any())->willReturn(new Response());

        $this->action->process(
            (new ServerRequest())->withAttribute('shortCode', $shortCode),
            $delegate->reveal()
        );

        $process->shouldHaveBeenCalledOnce();
    }
}
