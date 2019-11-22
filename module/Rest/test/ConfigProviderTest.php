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
        $config = $this->configProvider->__invoke();

        $this->assertArrayHasKey('routes', $config);
        $this->assertArrayHasKey('dependencies', $config);
    }
}
