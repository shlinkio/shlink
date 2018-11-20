<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Middleware;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\Middleware\QrCodeCacheMiddleware;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Uri;

class QrCodeCacheMiddlewareTest extends TestCase
{
    /** @var QrCodeCacheMiddleware */
    private $middleware;
    /** @var Cache */
    private $cache;

    public function setUp()
    {
        $this->cache = new ArrayCache();
        $this->middleware = new QrCodeCacheMiddleware($this->cache);
    }

    /**
     * @test
     */
    public function noCachedPathFallsBackToNextMiddleware()
    {
        $delegate = $this->prophesize(RequestHandlerInterface::class);
        $delegate->handle(Argument::any())->willReturn(new Response())->shouldBeCalledOnce();

        $this->middleware->process(ServerRequestFactory::fromGlobals()->withUri(
            new Uri('/foo/bar')
        ), $delegate->reveal());

        $this->assertTrue($this->cache->contains('/foo/bar'));
    }

    /**
     * @test
     */
    public function cachedPathReturnsCacheContent()
    {
        $isCalled = false;
        $uri = (new Uri())->withPath('/foo');
        $this->cache->save('/foo', ['body' => 'the body', 'content-type' => 'image/png']);
        $delegate = $this->prophesize(RequestHandlerInterface::class);

        $resp = $this->middleware->process(
            ServerRequestFactory::fromGlobals()->withUri($uri),
            $delegate->reveal()
        );

        $this->assertFalse($isCalled);
        $resp->getBody()->rewind();
        $this->assertEquals('the body', $resp->getBody()->getContents());
        $this->assertEquals('image/png', $resp->getHeaderLine('Content-Type'));
        $delegate->handle(Argument::any())->shouldHaveBeenCalledTimes(0);
    }
}
