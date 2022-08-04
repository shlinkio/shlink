<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest;

use Laminas\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Rest\ConfigProvider;

class ConfigProviderTest extends TestCase
{
    private ConfigProvider $configProvider;

    public function setUp(): void
    {
        $this->configProvider = new ConfigProvider();
    }

    /** @test */
    public function properConfigIsReturned(): void
    {
        $config = ($this->configProvider)();

        self::assertCount(4, $config);
        self::assertArrayHasKey('dependencies', $config);
        self::assertArrayHasKey('auth', $config);
        self::assertArrayHasKey('entity_manager', $config);
        self::assertArrayHasKey(ConfigAbstractFactory::class, $config);
    }

    /**
     * @test
     * @dataProvider provideRoutesConfig
     */
    public function routesAreProperlyPrefixed(array $routes, bool $multiSegmentEnabled, array $expected): void
    {
        self::assertEquals($expected, ConfigProvider::applyRoutesPrefix($routes, $multiSegmentEnabled));
    }

    public function provideRoutesConfig(): iterable
    {
        yield 'health action present' => [
            [
                ['path' => '/foo'],
                ['path' => '/bar'],
                ['path' => '/baz/foo'],
                ['path' => '/health'],
            ],
            false,
            [
                ['path' => '/rest/v{version:1|2}/foo'],
                ['path' => '/rest/v{version:1|2}/bar'],
                ['path' => '/rest/v{version:1|2}/baz/foo'],
                ['path' => '/rest/v{version:1|2}/health'],
                ['path' => '/rest/health', 'name' => ConfigProvider::UNVERSIONED_HEALTH_ENDPOINT_NAME],
            ],
        ];
        yield 'health action not present' => [
            [
                ['path' => '/foo'],
                ['path' => '/bar'],
                ['path' => '/baz/foo'],
            ],
            false,
            [
                ['path' => '/rest/v{version:1|2}/foo'],
                ['path' => '/rest/v{version:1|2}/bar'],
                ['path' => '/rest/v{version:1|2}/baz/foo'],
            ],
        ];
        yield 'multi-segment enabled' => [
            [
                ['path' => '/foo'],
                ['path' => '/bar/{shortCode}'],
                ['path' => '/baz/{shortCode}/foo'],
            ],
            true,
            [
                ['path' => '/rest/v{version:1|2}/foo'],
                ['path' => '/rest/v{version:1|2}/bar/{shortCode:.+}'],
                ['path' => '/rest/v{version:1|2}/baz/{shortCode:.+}/foo'],
            ],
        ];
    }
}
