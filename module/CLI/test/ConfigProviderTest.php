<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\CLI\ConfigProvider;

class ConfigProviderTest extends TestCase
{
    /** @var ConfigProvider */
    protected $configProvider;

    public function setUp()
    {
        $this->configProvider = new ConfigProvider();
    }

    /**
     * @test
     */
    public function confiIsProperlyReturned()
    {
        $config = ($this->configProvider)();

        $this->assertArrayHasKey('cli', $config);
        $this->assertArrayHasKey('dependencies', $config);
    }
}
