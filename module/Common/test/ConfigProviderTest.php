<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\ConfigProvider;

class ConfigProviderTest extends TestCase
{
    /** @var ConfigProvider */
    private $configProvider;

    public function setUp(): void
    {
        $this->configProvider = new ConfigProvider();
    }

    /** @test */
    public function configIsReturned()
    {
        $config = $this->configProvider->__invoke();

        $this->assertArrayHasKey('dependencies', $config);
        $this->assertArrayHasKey('plates', $config);
    }
}
