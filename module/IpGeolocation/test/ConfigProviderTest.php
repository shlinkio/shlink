<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\IpGeolocation;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\IpGeolocation\ConfigProvider;
use Zend\ServiceManager\AbstractFactory\ConfigAbstractFactory;

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
        $config = $this->configProvider->__invoke();

        $this->assertArrayHasKey('dependencies', $config);
        $this->assertArrayHasKey(ConfigAbstractFactory::class, $config);
    }
}
