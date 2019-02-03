<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Installer\Factory;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Installer\Factory\InstallApplicationFactory;
use Symfony\Component\Console\Application;
use Symfony\Component\Filesystem\Filesystem;
use Zend\ServiceManager\ServiceManager;

class InstallApplicationFactoryTest extends TestCase
{
    /** @var InstallApplicationFactory */
    private $factory;

    public function setUp(): void
    {
        $this->factory = new InstallApplicationFactory();
    }

    /**
     * @test
     */
    public function serviceIsCreated(): void
    {
        $instance = ($this->factory)(new ServiceManager(['services' => [
            Filesystem::class => $this->prophesize(Filesystem::class)->reveal(),
            'config' => ['config_customizer_plugins' => []],
        ]]), '');

        $this->assertInstanceOf(Application::class, $instance);
    }
}
