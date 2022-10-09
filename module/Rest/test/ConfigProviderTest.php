<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest;

use Laminas\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Rest\ConfigProvider;

class ConfigProviderTest extends TestCase
{
    private ConfigProvider $configProvider;

    protected function setUp(): void
    {
        $this->configProvider = new ConfigProvider();
    }

    /** @test */
    public function properConfigIsReturned(): void
    {
        $config = ($this->configProvider)();

        self::assertCount(5, $config);
        self::assertArrayHasKey('dependencies', $config);
        self::assertArrayHasKey('auth', $config);
        self::assertArrayHasKey('entity_manager', $config);
        self::assertArrayHasKey('initial_api_key', $config);
        self::assertArrayHasKey(ConfigAbstractFactory::class, $config);
    }
}
