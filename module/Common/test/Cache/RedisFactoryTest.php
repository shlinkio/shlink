<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\Cache;

use PHPUnit\Framework\TestCase;
use Predis\Connection\Aggregate\PredisCluster;
use Predis\Connection\Aggregate\RedisCluster;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Shlinkio\Shlink\Common\Cache\RedisFactory;

class RedisFactoryTest extends TestCase
{
    /** @var RedisFactory */
    private $factory;
    /** @var ObjectProphecy */
    private $container;

    public function setUp(): void
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->factory = new RedisFactory();
    }

    /**
     * @test
     * @dataProvider provideRedisConfig
     */
    public function createsRedisClientBasedOnRedisConfig(?array $config, string $expectedCluster): void
    {
        $getConfig = $this->container->get('config')->willReturn([
            'redis' => $config,
        ]);

        $client = ($this->factory)($this->container->reveal());

        $getConfig->shouldHaveBeenCalledOnce();
        $this->assertInstanceOf($expectedCluster, $client->getOptions()->cluster);
    }

    /**
     * @test
     * @dataProvider provideRedisConfig
     */
    public function createsRedisClientBasedOnCacheConfig(?array $config, string $expectedCluster): void
    {
        $getConfig = $this->container->get('config')->willReturn([
            'cache' => [
                'redis' => $config,
            ],
        ]);

        $client = ($this->factory)($this->container->reveal());

        $getConfig->shouldHaveBeenCalledOnce();
        $this->assertInstanceOf($expectedCluster, $client->getOptions()->cluster);
    }

    public function provideRedisConfig(): iterable
    {
        yield 'no config' => [null, PredisCluster::class];
        yield 'single server as string' => [[
            'servers' => 'tcp://127.0.0.1:6379',
        ], PredisCluster::class];
        yield 'single server as array' => [[
            'servers' => ['tcp://127.0.0.1:6379'],
        ], PredisCluster::class];
        yield 'cluster of servers' => [[
            'servers' => ['tcp://1.1.1.1:6379', 'tcp://2.2.2.2:6379'],
        ], RedisCluster::class];
        yield 'empty cluster of servers' => [[
            'servers' => [],
        ], PredisCluster::class];
        yield 'cluster of servers as string' => [[
            'servers' => 'tcp://1.1.1.1:6379,tcp://2.2.2.2:6379',
        ], RedisCluster::class];
    }
}
