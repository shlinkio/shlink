<?php
namespace ShlinkioTest\Shlink\Core\Middleware;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use PHPUnit_Framework_TestCase as TestCase;
use Shlinkio\Shlink\Core\Middleware\QrCodeCacheMiddleware;
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
    public function noCachedPathFallbacksToNextMiddleware()
    {
        $isCalled = false;
        $this->middleware->__invoke(
            ServerRequestFactory::fromGlobals(),
            new Response(),
            function ($req, $resp) use (&$isCalled) {
                $isCalled = true;
                return $resp;
            }
        );
        $this->assertTrue($isCalled);
    }

    /**
     * @test
     */
    public function cachedPathReturnsCacheContent()
    {
        $isCalled = false;
        $uri = (new Uri())->withPath('/foo');
        $this->cache->save('/foo', ['body' => 'the body', 'content-type' => 'image/png']);

        $resp = $this->middleware->__invoke(
            ServerRequestFactory::fromGlobals()->withUri($uri),
            new Response(),
            function ($req, $resp) use (&$isCalled) {
                $isCalled = true;
                return $resp;
            }
        );

        $this->assertFalse($isCalled);
        $resp->getBody()->rewind();
        $this->assertEquals('the body', $resp->getBody()->getContents());
        $this->assertEquals('image/png', $resp->getHeaderLine('Content-Type'));
    }
}
