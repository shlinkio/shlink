<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\Cache;

use Doctrine\Common\Cache;
use PHPUnit\Framework\TestCase;
use Predis\ClientInterface;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Shlinkio\Shlink\Common\Cache\CacheFactory;
use Shlinkio\Shlink\Common\Cache\RedisFactory;

class CacheFactoryTest extends TestCase
{
    /** @var ObjectProphecy */
    private $container;

    public function setUp(): void
    {
        $this->container = $this->prophesize(ContainerInterface::class);
    }

    /**
     * @test
     * @dataProvider provideCacheConfig
     */
    public function expectedCacheAdapterIsReturned(
        array $config,
        string $expectedAdapterClass,
        string $expectedNamespace,
        ?callable $apcuEnabled = null
    ): void {
        $factory = new CacheFactory($apcuEnabled);

        $getConfig = $this->container->get('config')->willReturn($config);
        $getRedis = $this->container->get(RedisFactory::SERVICE_NAME)->willReturn(
            $this->prophesize(ClientInterface::class)->reveal()
        );

        $cache = $factory($this->container->reveal());

        $this->assertInstanceOf($expectedAdapterClass, $cache);
        $this->assertEquals($expectedNamespace, $cache->getNamespace());
        $getConfig->shouldHaveBeenCalledOnce();
        $getRedis->shouldHaveBeenCalledTimes($expectedAdapterClass === Cache\PredisCache::class ? 1 :0);
    }

    public function provideCacheConfig(): iterable
    {
        yield 'debug true' => [['debug' => true], Cache\ArrayCache::class, ''];
        yield 'debug false' => [['debug' => false], Cache\ApcuCache::class, ''];
        yield 'no debug' => [[], Cache\ApcuCache::class, ''];
        yield 'with redis' => [['cache' => [
            'namespace' => $namespace = 'some_namespace',
            'redis' => [],
        ]], Cache\PredisCache::class, $namespace];
        yield 'debug false and no apcu' => [['debug' => false], Cache\ArrayCache::class, '', function () {
            return false;
        }];
    }
}
