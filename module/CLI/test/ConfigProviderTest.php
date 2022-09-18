<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI;

use Laminas\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\CLI\ConfigProvider;

class ConfigProviderTest extends TestCase
{
    private ConfigProvider $configProvider;

    protected function setUp(): void
    {
        $this->configProvider = new ConfigProvider();
    }

    /** @test */
    public function configIsProperlyReturned(): void
    {
        $config = ($this->configProvider)();

        self::assertCount(3, $config);
        self::assertArrayHasKey('cli', $config);
        self::assertArrayHasKey('dependencies', $config);
        self::assertArrayHasKey(ConfigAbstractFactory::class, $config);
    }
}
