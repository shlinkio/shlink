<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\ConfigProvider;

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
}
