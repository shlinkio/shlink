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
        $choice->shouldHaveBeenCalledOnce();
        $ask->shouldHaveBeenCalledTimes(5);
    }

    /**
     * @test
     */
    public function onlyMissingOptionsAreAsked()
    {
        $choice = $this->io->choice(Argument::cetera())->willReturn('MySQL');
        $ask = $this->io->ask(Argument::cetera())->willReturn('asked');

        $config = new CustomizableAppConfig();
        $config->setDatabase([
            'DRIVER' => 'pdo_pgsql',
            'NAME' => 'foo',
            'PASSWORD' => 'foo',
        ]);

        $this->plugin->process($this->io->reveal(), $config);

        $this->assertEquals([
            'DRIVER' => 'pdo_pgsql',
            'NAME' => 'foo',
            'USER' => 'asked',
            'PASSWORD' => 'foo',
            'HOST' => 'asked',
            'PORT' => 'asked',
        ], $config->getDatabase());
        $choice->shouldNotHaveBeenCalled();
        $ask->shouldHaveBeenCalledTimes(3);
    }

    /**
     * @test
     */
    public function noQuestionsAskedIfImportedConfigContainsEverything()
    {
        $choice = $this->io->choice(Argument::cetera())->willReturn('MySQL');
        $ask = $this->io->ask(Argument::cetera())->willReturn('asked');

        $config = new CustomizableAppConfig();
        $config->setDatabase([
            'DRIVER' => 'pdo_pgsql',
            'NAME' => 'foo',
            'USER' => 'foo',
            'PASSWORD' => 'foo',
            'HOST' => 'foo',
            'PORT' => 'foo',
        ]);

        $this->plugin->process($this->io->reveal(), $config);

        $this->assertEquals([
            'DRIVER' => 'pdo_pgsql',
            'NAME' => 'foo',
            'USER' => 'foo',
            'PASSWORD' => 'foo',
            'HOST' => 'foo',
            'PORT' => 'foo',
        ], $config->getDatabase());
        $choice->shouldNotHaveBeenCalled();
        $ask->shouldNotHaveBeenCalled();
    }

    /**
     * @test
     */
    public function sqliteDatabaseIsImportedWhenRequested()
    {
        $copy = $this->filesystem->copy(Argument::cetera())->willReturn(null);

        $config = new CustomizableAppConfig();
        $config->setDatabase([
            'DRIVER' => 'pdo_sqlite',
        ]);

        $this->plugin->process($this->io->reveal(), $config);

        $this->assertEquals([
            'DRIVER' => 'pdo_sqlite',
        ], $config->getDatabase());
        $copy->shouldHaveBeenCalledOnce();
    }
}
