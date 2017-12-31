<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Install\Plugin;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Install\Plugin\DatabaseConfigCustomizer;
use Shlinkio\Shlink\CLI\Model\CustomizableAppConfig;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

class DatabaseConfigCustomizerPluginTest extends TestCase
{
    /**
     * @var DatabaseConfigCustomizer
     */
    private $plugin;
    /**
     * @var ObjectProphecy
     */
    private $questionHelper;
    /**
     * @var ObjectProphecy
     */
    private $filesystem;

    public function setUp()
    {
        $this->questionHelper = $this->prophesize(QuestionHelper::class);
        $this->filesystem = $this->prophesize(Filesystem::class);

        $this->plugin = new DatabaseConfigCustomizer(
            $this->questionHelper->reveal(),
            $this->filesystem->reveal()
        );
    }

    /**
     * @test
     */
    public function configIsRequestedToTheUser()
    {
        /** @var MethodProphecy $askSecret */
        $askSecret = $this->questionHelper->ask(Argument::cetera())->willReturn('MySQL');
        $config = new CustomizableAppConfig();

        $this->plugin->process(new SymfonyStyle(new ArrayInput([]), new NullOutput()), $config);

        $this->assertTrue($config->hasDatabase());
        $this->assertEquals([
            'DRIVER' => 'pdo_mysql',
            'NAME' => 'MySQL',
            'USER' => 'MySQL',
            'PASSWORD' => 'MySQL',
            'HOST' => 'MySQL',
            'PORT' => 'MySQL',
        ], $config->getDatabase());
        $askSecret->shouldHaveBeenCalledTimes(6);
    }

    /**
     * @test
     */
    public function overwriteIsRequestedIfValueIsAlreadySet()
    {
        /** @var MethodProphecy $ask */
        $ask = $this->questionHelper->ask(Argument::cetera())->will(function (array $args) {
            $last = array_pop($args);
            return $last instanceof ConfirmationQuestion ? false : 'MySQL';
        });
        $config = new CustomizableAppConfig();
        $config->setDatabase([
            'DRIVER' => 'pdo_pgsql',
            'NAME' => 'MySQL',
            'USER' => 'MySQL',
            'PASSWORD' => 'MySQL',
            'HOST' => 'MySQL',
            'PORT' => 'MySQL',
        ]);

        $this->plugin->process(new SymfonyStyle(new ArrayInput([]), new NullOutput()), $config);

        $this->assertEquals([
            'DRIVER' => 'pdo_mysql',
            'NAME' => 'MySQL',
            'USER' => 'MySQL',
            'PASSWORD' => 'MySQL',
            'HOST' => 'MySQL',
            'PORT' => 'MySQL',
        ], $config->getDatabase());
        $ask->shouldHaveBeenCalledTimes(7);
    }

    /**
     * @test
     */
    public function existingValueIsKeptIfRequested()
    {
        /** @var MethodProphecy $ask */
        $ask = $this->questionHelper->ask(Argument::cetera())->willReturn(true);

        $config = new CustomizableAppConfig();
        $config->setDatabase([
            'DRIVER' => 'pdo_pgsql',
            'NAME' => 'MySQL',
            'USER' => 'MySQL',
            'PASSWORD' => 'MySQL',
            'HOST' => 'MySQL',
            'PORT' => 'MySQL',
        ]);

        $this->plugin->process(new SymfonyStyle(new ArrayInput([]), new NullOutput()), $config);

        $this->assertEquals([
            'DRIVER' => 'pdo_pgsql',
            'NAME' => 'MySQL',
            'USER' => 'MySQL',
            'PASSWORD' => 'MySQL',
            'HOST' => 'MySQL',
            'PORT' => 'MySQL',
        ], $config->getDatabase());
        $ask->shouldHaveBeenCalledTimes(1);
    }

    /**
     * @test
     */
    public function sqliteDatabaseIsImportedWhenRequested()
    {
        /** @var MethodProphecy $ask */
        $ask = $this->questionHelper->ask(Argument::cetera())->willReturn(true);
        /** @var MethodProphecy $copy */
        $copy = $this->filesystem->copy(Argument::cetera())->willReturn(null);

        $config = new CustomizableAppConfig();
        $config->setDatabase([
            'DRIVER' => 'pdo_sqlite',
        ]);

        $this->plugin->process(new SymfonyStyle(new ArrayInput([]), new NullOutput()), $config);

        $this->assertEquals([
            'DRIVER' => 'pdo_sqlite',
        ], $config->getDatabase());
        $ask->shouldHaveBeenCalledTimes(1);
        $copy->shouldHaveBeenCalledTimes(1);
    }
}
