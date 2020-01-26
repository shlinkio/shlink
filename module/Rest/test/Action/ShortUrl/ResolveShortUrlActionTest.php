<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\ShortUrl;

use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Service\ShortUrl\ShortUrlResolverInterface;
use Shlinkio\Shlink\Rest\Action\ShortUrl\ResolveShortUrlAction;

use function strpos;

class ResolveShortUrlActionTest extends TestCase
{
    private ResolveShortUrlAction $action;
    private ObjectProphecy $urlResolver;

    public function setUp(): void
    {
        $this->urlResolver = $this->prophesize(ShortUrlResolverInterface::class);
        $this->action = new ResolveShortUrlAction($this->urlResolver->reveal(), []);
    }

    /** @test */
    public function correctShortCodeReturnsSuccess(): void
    {
        $shortCode = 'abc123';
        $this->urlResolver->shortCodeToShortUrl($shortCode, null)->willReturn(
            new ShortUrl('http://domain.com/foo/bar'),
        )->shouldBeCalledOnce();

        $request = (new ServerRequest())->withAttribute('shortCode', $shortCode);
        $response = $this->action->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue(strpos($response->getBody()->getContents(), 'http://domain.com/foo/bar') > 0);
    }
}
