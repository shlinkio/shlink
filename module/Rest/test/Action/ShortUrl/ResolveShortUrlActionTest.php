<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\ShortUrl;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\EntityDoesNotExistException;
use Shlinkio\Shlink\Core\Service\UrlShortener;
use Shlinkio\Shlink\Rest\Action\ShortUrl\ResolveShortUrlAction;
use Shlinkio\Shlink\Rest\Util\RestUtils;
use Zend\Diactoros\ServerRequest;

use function strpos;

class ResolveShortUrlActionTest extends TestCase
{
    /** @var ResolveShortUrlAction */
    private $action;
    /** @var ObjectProphecy */
    private $urlShortener;

    public function setUp(): void
    {
        $this->urlShortener = $this->prophesize(UrlShortener::class);
        $this->action = new ResolveShortUrlAction($this->urlShortener->reveal(), []);
    }

    /** @test */
    public function incorrectShortCodeReturnsError(): void
    {
        $shortCode = 'abc123';
        $this->urlShortener->shortCodeToUrl($shortCode, null)->willThrow(EntityDoesNotExistException::class)
                                                             ->shouldBeCalledOnce();

        $request = (new ServerRequest())->withAttribute('shortCode', $shortCode);
        $response = $this->action->handle($request);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertTrue(strpos($response->getBody()->getContents(), RestUtils::INVALID_ARGUMENT_ERROR) > 0);
    }

    /** @test */
    public function correctShortCodeReturnsSuccess(): void
    {
        $shortCode = 'abc123';
        $this->urlShortener->shortCodeToUrl($shortCode, null)->willReturn(
            new ShortUrl('http://domain.com/foo/bar')
        )->shouldBeCalledOnce();

        $request = (new ServerRequest())->withAttribute('shortCode', $shortCode);
        $response = $this->action->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue(strpos($response->getBody()->getContents(), 'http://domain.com/foo/bar') > 0);
    }
}
