<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Authentication;

use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Rest\Authentication\AuthenticationPluginManager;
use Shlinkio\Shlink\Rest\Authentication\AuthenticationPluginManagerFactory;
use Shlinkio\Shlink\Rest\Authentication\Plugin\AuthenticationPluginInterface;

class AuthenticationPluginManagerFactoryTest extends TestCase
{
    private AuthenticationPluginManagerFactory $factory;

    public function setUp(): void
    {
        $this->factory = new AuthenticationPluginManagerFactory();
    }

    /**
     * @test
     * @dataProvider provideConfigs
     */
    public function serviceIsProperlyCreatedWithExpectedPlugins(?array $config, array $expectedPlugins): void
    {
        $instance = ($this->factory)(new ServiceManager(['services' => [
            'config' => $config,
        ]]));

        self::assertEquals($expectedPlugins, $this->getPlugins($instance));
    }

    private function getPlugins(AuthenticationPluginManager $pluginManager): array
    {
        return (fn () => $this->services)->call($pluginManager);
    }

    public function provideConfigs(): iterable
    {
        yield [null, []];
        yield [[], []];
        yield [['auth' => []], []];
        yield [['auth' => [
            'plugins' => [],
        ]], []];
        yield [['auth' => [
            'plugins' => [
                'services' => $plugins = [
                    'foo' => $this->prophesize(AuthenticationPluginInterface::class)->reveal(),
                    'bar' => $this->prophesize(AuthenticationPluginInterface::class)->reveal(),
                ],
            ],
        ]], $plugins];
    }
}
