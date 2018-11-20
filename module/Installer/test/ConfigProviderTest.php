<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Installer;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Installer\ConfigProvider;

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
    public function configIsReturned()
    {
        $config = $this->configProvider->__invoke();
        $this->assertEmpty($config);
    }
}
