<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Installer\Config\Plugin;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Installer\Config\Plugin\DatabaseConfigCustomizer;
use Shlinkio\Shlink\Installer\Model\CustomizableAppConfig;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

class DatabaseConfigCustomizerTest extends TestCase
{
    /**
     * @var DatabaseConfigCustomizer
     */
    private $plugin;
    /**
     * @var ObjectProphecy
     */
    private $io;
    /**
     * @var ObjectProphecy
     */
    private $filesystem;

    public function setUp()
    {
        $this->io = $this->prophesize(SymfonyStyle::class);
        $this->io->title(Argument::any())->willReturn(null);
        $this->filesystem = $this->prophesize(Filesystem::class);

        $this->plugin = new DatabaseConfigCustomizer($this->filesystem->reveal());
    }

    /**
     * @test
     */
    public function configIsRequestedToTheUser()
    {
        $choice = $this->io->choice(Argument::cetera())->willReturn('MySQL');
        $ask = $this->io->ask(Argument::cetera())->willReturn('param');
        $config = new CustomizableAppConfig();

        $this->plugin->process($this->io->reveal(), $config);

        $this->assertTrue($config->hasDatabase());
        $this->assertEquals([
            'DRIVER' => 'pdo_mysql',
            'NAME' => 'param',
            'USER' => 'param',
            'PASSWORD' => 'param',
            'HOST' => 'param',
            'PORT' => 'param',
        ], $config->getDatabase());
        $choice->shouldHaveBeenCalledTimes(1);
        $ask->shouldHaveBeenCalledTimes(5);
    }

    /**
     * @test
     */
    public function overwriteIsRequestedIfValueIsAlreadySet()
    {
        $choice = $this->io->choice(Argument::cetera())->willReturn('MySQL');
        $confirm = $this->io->confirm(Argument::cetera())->willReturn(false);
        $ask = $this->io->ask(Argument::cetera())->willReturn('MySQL');
        $config = new CustomizableAppConfig();
        $config->setDatabase([
            'DRIVER' => 'pdo_pgsql',
            'NAME' => 'MySQL',
            'USER' => 'MySQL',
            'PASSWORD' => 'MySQL',
            'HOST' => 'MySQL',
            'PORT' => 'MySQL',
        ]);

        $this->plugin->process($this->io->reveal(), $config);

        $this->assertEquals([
            'DRIVER' => 'pdo_mysql',
            'NAME' => 'MySQL',
            'USER' => 'MySQL',
            'PASSWORD' => 'MySQL',
            'HOST' => 'MySQL',
            'PORT' => 'MySQL',
        ], $config->getDatabase());
        $confirm->shouldHaveBeenCalledTimes(1);
        $choice->shouldHaveBeenCalledTimes(1);
        $ask->shouldHaveBeenCalledTimes(5);
    }

    /**
     * @test
     */
    public function existingValueIsKeptIfRequested()
    {
        $confirm = $this->io->confirm(Argument::cetera())->willReturn(true);

        $config = new CustomizableAppConfig();
        $config->setDatabase([
            'DRIVER' => 'pdo_pgsql',
            'NAME' => 'MySQL',
            'USER' => 'MySQL',
            'PASSWORD' => 'MySQL',
            'HOST' => 'MySQL',
            'PORT' => 'MySQL',
        ]);

        $this->plugin->process($this->io->reveal(), $config);

        $this->assertEquals([
            'DRIVER' => 'pdo_pgsql',
            'NAME' => 'MySQL',
            'USER' => 'MySQL',
            'PASSWORD' => 'MySQL',
            'HOST' => 'MySQL',
            'PORT' => 'MySQL',
        ], $config->getDatabase());
        $confirm->shouldHaveBeenCalledTimes(1);
    }

    /**
     * @test
     */
    public function sqliteDatabaseIsImportedWhenRequested()
    {
        $confirm = $this->io->confirm(Argument::cetera())->willReturn(true);
        $copy = $this->filesystem->copy(Argument::cetera())->willReturn(null);

        $config = new CustomizableAppConfig();
        $config->setDatabase([
            'DRIVER' => 'pdo_sqlite',
        ]);

        $this->plugin->process($this->io->reveal(), $config);

        $this->assertEquals([
            'DRIVER' => 'pdo_sqlite',
        ], $config->getDatabase());
        $confirm->shouldHaveBeenCalledTimes(1);
        $copy->shouldHaveBeenCalledTimes(1);
    }
}
