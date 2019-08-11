<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\PreviewGenerator;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\PreviewGenerator\ConfigProvider;

class ConfigProviderTest extends TestCase
{
    /** @var ConfigProvider */
    private $configProvider;

    public function setUp(): void
    {
        $this->configProvider = new ConfigProvider();
    }

    /** @test */
    public function configIsReturned(): void
    {
        $config = ($this->configProvider)();

        $this->assertArrayHasKey('dependencies', $config);
    }
}
