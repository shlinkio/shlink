<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Rest\ConfigProvider;

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

        $this->assertArrayHasKey('error_handler', $config);
        $this->assertArrayHasKey('routes', $config);
        $this->assertArrayHasKey('dependencies', $config);
    }
}
