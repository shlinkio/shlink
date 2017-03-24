<?php
namespace ShlinkioTest\Shlink\Core\Middleware;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Shlinkio\Shlink\Core\Middleware\QrCodeCacheMiddleware;
use ShlinkioTest\Shlink\Common\Util\TestUtils;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Uri;

class QrCodeCacheMiddlewareTest extends TestCase
{
    /**
     * @var QrCodeCacheMiddleware
     */
    protected $middleware;
    /**
     * @var Cache
     */
    protected $cache;

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
        $delegate = TestUtils::createDelegateMock();
        $this->middleware->process(ServerRequestFactory::fromGlobals()->withUri(
            new Uri('/foo/bar')
        ), $delegate->reveal());

        $this->assertTrue($this->cache->contains('/foo/bar'));
        $delegate->process(Argument::any())->shouldHaveBeenCalledTimes(1);
    }

    /**
     * @test
     */
    public function cachedPathReturnsCacheContent()
    {
        $isCalled = false;
        $uri = (new Uri())->withPath('/foo');
        $this->cache->save('/foo', ['body' => 'the body', 'content-type' => 'image/png']);
        $delegate = TestUtils::createDelegateMock();

        $resp = $this->middleware->process(
            ServerRequestFactory::fromGlobals()->withUri($uri),
            $delegate->reveal()
        );

        $this->assertFalse($isCalled);
        $resp->getBody()->rewind();
        $this->assertEquals('the body', $resp->getBody()->getContents());
        $this->assertEquals('image/png', $resp->getHeaderLine('Content-Type'));
        $delegate->process(Argument::any())->shouldHaveBeenCalledTimes(0);
    }
}
