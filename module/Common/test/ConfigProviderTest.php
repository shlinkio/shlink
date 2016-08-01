<?php
namespace ShlinkioTest\Shlink\Common;

use PHPUnit_Framework_TestCase as TestCase;
use Shlinkio\Shlink\Common\ConfigProvider;

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
    public function configIsReturned()
    {
        $config = $this->configProvider->__invoke();

        $this->assertArrayHasKey('error_handler', $config);
        $this->assertArrayHasKey('middleware_pipeline', $config);
        $this->assertArrayHasKey('dependencies', $config);
        $this->assertArrayHasKey('twig', $config);
    }
}
