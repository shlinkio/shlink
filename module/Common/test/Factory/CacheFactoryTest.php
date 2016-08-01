<?php
namespace ShlinkioTest\Shlink\Common\Factory;

use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\FilesystemCache;
use PHPUnit_Framework_TestCase as TestCase;
use Shlinkio\Shlink\Common\Factory\CacheFactory;
use Zend\ServiceManager\ServiceManager;

class CacheFactoryTest extends TestCase
{
    /**
     * @var CacheFactory
     */
    protected $factory;

    public function setUp()
    {
        $this->factory = new CacheFactory();
    }

    public static function tearDownAfterClass()
    {
        putenv('APP_ENV');
    }

    /**
     * @test
     */
    public function productionReturnsApcAdapter()
    {
        putenv('APP_ENV=pro');
        $instance = $this->factory->__invoke($this->createSM(), '');
        $this->assertInstanceOf(ApcuCache::class, $instance);
    }

    /**
     * @test
     */
    public function developmentReturnsArrayAdapter()
    {
        putenv('APP_ENV=dev');
        $instance = $this->factory->__invoke($this->createSM(), '');
        $this->assertInstanceOf(ArrayCache::class, $instance);
    }

    /**
     * @test
     */
    public function adapterDefinedInConfigIgnoresEnvironment()
    {
        putenv('APP_ENV=pro');
        $instance = $this->factory->__invoke($this->createSM(ArrayCache::class), '');
        $this->assertInstanceOf(ArrayCache::class, $instance);
    }

    /**
     * @test
     */
    public function invalidAdapterDefinedInConfigFallbacksToEnvironment()
    {
        putenv('APP_ENV=pro');
        $instance = $this->factory->__invoke($this->createSM(FilesystemCache::class), '');
        $this->assertInstanceOf(ApcuCache::class, $instance);
    }

    private function createSM($cacheAdapter = null)
    {
        return new ServiceManager(['services' => [
            'config' => isset($cacheAdapter) ? [
                'cache' => ['adapter' => $cacheAdapter],
            ] : [],
        ]]);
    }
}
