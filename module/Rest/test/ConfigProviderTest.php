<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest;

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

        self::assertArrayHasKey('routes', $config);
        self::assertArrayHasKey('dependencies', $config);
    }

    /**
     * @test
     * @dataProvider provideRoutesConfig
     */
    public function routesAreProperlyPrefixed(array $routes, array $expected): void
    {
        $configProvider = new ConfigProvider(fn () => ['routes' => $routes]);

        $config = $configProvider();

        self::assertEquals($expected, $config['routes']);
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
            [
                ['path' => '/rest/v{version:1|2}/foo'],
                ['path' => '/rest/v{version:1|2}/bar'],
                ['path' => '/rest/v{version:1|2}/baz/foo'],
            ],
        ];
    }
}
