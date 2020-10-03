<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\CLI\ConfigProvider;

class ConfigProviderTest extends TestCase
{
    private ConfigProvider $configProvider;

    public function setUp(): void
    {
        $this->configProvider = new ConfigProvider();
    }

    /** @test */
    public function configIsProperlyReturned(): void
    {
        $config = ($this->configProvider)();

        self::assertArrayHasKey('cli', $config);
        self::assertArrayHasKey('dependencies', $config);
    }
}
