<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\ConfigProvider;

class ConfigProviderTest extends TestCase
{
    /** @var ConfigProvider */
    private $configProvider;

    public function setUp(): void
    {
        $this->configProvider = new ConfigProvider();
    }

    /** @test */
    public function properConfigIsReturned()
    {
        $config = $this->configProvider->__invoke();

        $this->assertArrayHasKey('routes', $config);
        $this->assertArrayHasKey('dependencies', $config);
        $this->assertArrayHasKey('templates', $config);
        $this->assertArrayHasKey('zend-expressive', $config);
    }
}
