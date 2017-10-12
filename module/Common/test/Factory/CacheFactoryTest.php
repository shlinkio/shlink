<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\Factory;

use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\FilesystemCache;
use Doctrine\Common\Cache\MemcachedCache;
use Doctrine\Common\Cache\RedisCache;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\Factory\CacheFactory;
use Shlinkio\Shlink\Core\Options\AppOptions;
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
        $instance = $this->factory->__invoke($this->createSM(RedisCache::class), '');
        $this->assertInstanceOf(ApcuCache::class, $instance);
    }

    /**
     * @test
     */
    public function filesystemCacheAdaptersReadDirOption()
    {
        $dir = sys_get_temp_dir();
        /** @var FilesystemCache $instance */
        $instance = $this->factory->__invoke($this->createSM(FilesystemCache::class, ['dir' => $dir]), '');
        $this->assertInstanceOf(FilesystemCache::class, $instance);
        $this->assertEquals($dir, $instance->getDirectory());
    }

    /**
     * @test
     */
    public function memcachedCacheAdaptersReadServersOption()
    {
        $servers = [
            [
                'host' => '1.2.3.4',
                'port' => 123,
            ],
            [
                'host' => '4.3.2.1',
                'port' => 321,
            ],
        ];
        /** @var MemcachedCache $instance */
        $instance = $this->factory->__invoke($this->createSM(MemcachedCache::class, ['servers' => $servers]), '');
        $this->assertInstanceOf(MemcachedCache::class, $instance);
        $this->assertEquals(count($servers), count($instance->getMemcached()->getServerList()));
    }

    private function createSM($cacheAdapter = null, array $options = [])
    {
        return new ServiceManager(['services' => [
            'config' => isset($cacheAdapter) ? [
                'cache' => [
                    'adapter' => $cacheAdapter,
                    'options' => $options,
                ],
            ] : [],
            AppOptions::class => new AppOptions(),
        ]]);
    }
}
