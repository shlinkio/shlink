<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Rest\ConfigProvider;

class ConfigProviderTest extends TestCase
{
    /** @var ConfigProvider */
    private $configProvider;

    public function setUp(): void
    {
        $this->configProvider = new ConfigProvider();
    }

    /** @test */
    public function properConfigIsReturned(): void
    {
        $config = ($this->configProvider)();

        $this->assertArrayHasKey('routes', $config);
        $this->assertArrayHasKey('dependencies', $config);
    }

    /** @test */
    public function routesAreProperlyPrefixed(): void
    {
        $configProvider = new ConfigProvider(function () {
            return [
                'routes' => [
                    ['path' => '/foo'],
                    ['path' => '/bar'],
                    ['path' => '/baz/foo'],
                ],
            ];
        });

        $config = $configProvider();

        $this->assertEquals([
            ['path' => '/rest/v{version:1|2}/foo'],
            ['path' => '/rest/v{version:1|2}/bar'],
            ['path' => '/rest/v{version:1|2}/baz/foo'],
        ], $config['routes']);
    }
}
