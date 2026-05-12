<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ObjectMapper;

use CuyZ\Valinor\Cache\FileSystemCache;
use CuyZ\Valinor\Cache\FileWatchingCache;
use CuyZ\Valinor\Library\Settings;
use CuyZ\Valinor\MapperBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionObject;
use Shlinkio\Shlink\Core\Config\EnvVars;
use Shlinkio\Shlink\Core\ObjectMapper\MapperBuilderWithCacheDelegatorFactory;

use function putenv;

class MapperBuilderWithCacheDelegatorFactoryTest extends TestCase
{
    private ContainerInterface & Stub $container;
    private MapperBuilder $baseBuilder;

    protected function setUp(): void
    {
        $this->container = $this->createStub(ContainerInterface::class);
        $this->baseBuilder = new MapperBuilder();
    }

    protected function tearDown(): void
    {
        putenv(EnvVars::APP_ENV->value);
    }

    #[Test]
    #[TestWith(['dev'])]
    #[TestWith(['test'])]
    public function watchingFileSystemCacheIsSetForNonProdEnvs(string $env): void
    {
        putenv(EnvVars::APP_ENV->value . '=' . $env);

        $builder = new MapperBuilderWithCacheDelegatorFactory()($this->container, '', fn () => $this->baseBuilder);
        $reflection = new ReflectionObject($builder);
        /** @var Settings $settings */
        $settings = $reflection->getProperty('settings')->getValue($builder);

        self::assertNotSame($builder, $this->baseBuilder);
        self::assertInstanceOf(FileWatchingCache::class, $settings->cache);
    }

    #[Test]
    public function regularCacheIsSetForProdEnv(): void
    {
        putenv(EnvVars::APP_ENV->value . '=prod');

        $builder = new MapperBuilderWithCacheDelegatorFactory()($this->container, '', fn () => $this->baseBuilder);
        $reflection = new ReflectionObject($builder);
        /** @var Settings $settings */
        $settings = $reflection->getProperty('settings')->getValue($builder);

        self::assertNotSame($builder, $this->baseBuilder);
        self::assertInstanceOf(FileSystemCache::class, $settings->cache);
    }
}
