<?php
namespace ShlinkioTest\Shlink\Core;

use PHPUnit_Framework_TestCase as TestCase;
use Shlinkio\Shlink\Core\ConfigProvider;

class ConfigProviderTest extends TestCase
{
    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    public function setUp()
    {
        $this->configProvider = new ConfigProvider();
    }

    /**
     * @test
     */
    public function properConfigIsReturned()
    {
        $config = $this->configProvider->__invoke();

        $this->assertArrayHasKey('routes', $config);
        $this->assertArrayHasKey('dependencies', $config);
        $this->assertArrayHasKey('templates', $config);
        $this->assertArrayHasKey('translator', $config);
        $this->assertArrayHasKey('zend-expressive', $config);
    }
}
