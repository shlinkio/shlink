<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Installer\Config\Plugin;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Installer\Config\Plugin\LanguageConfigCustomizer;
use Shlinkio\Shlink\Installer\Model\CustomizableAppConfig;
use Symfony\Component\Console\Style\SymfonyStyle;

class LanguageConfigCustomizerTest extends TestCase
{
    /**
     * @var LanguageConfigCustomizer
     */
    protected $plugin;
    /**
     * @var ObjectProphecy
     */
    protected $io;

    public function setUp()
    {
        $this->io = $this->prophesize(SymfonyStyle::class);
        $this->io->title(Argument::any())->willReturn(null);
        $this->plugin = new LanguageConfigCustomizer();
    }

    /**
     * @test
     */
    public function configIsRequestedToTheUser()
    {
        $ask = $this->io->choice(Argument::cetera())->willReturn('en');
        $config = new CustomizableAppConfig();

        $this->plugin->process($this->io->reveal(), $config);

        $this->assertTrue($config->hasLanguage());
        $this->assertEquals([
            'DEFAULT' => 'en',
            'CLI' => 'en',
        ], $config->getLanguage());
        $ask->shouldHaveBeenCalledTimes(2);
    }

    /**
     * @test
     */
    public function overwriteIsRequestedIfValueIsAlreadySet()
    {
        $choice = $this->io->choice(Argument::cetera())->willReturn('es');
        $confirm = $this->io->confirm(Argument::cetera())->willReturn(false);
        $config = new CustomizableAppConfig();
        $config->setLanguage([
            'DEFAULT' => 'en',
            'CLI' => 'en',
        ]);

        $this->plugin->process($this->io->reveal(), $config);

        $this->assertEquals([
            'DEFAULT' => 'es',
            'CLI' => 'es',
        ], $config->getLanguage());
        $choice->shouldHaveBeenCalledTimes(2);
        $confirm->shouldHaveBeenCalledTimes(1);
    }

    /**
     * @test
     */
    public function existingValueIsKeptIfRequested()
    {
        $ask = $this->io->confirm(Argument::cetera())->willReturn(true);

        $config = new CustomizableAppConfig();
        $config->setLanguage([
            'DEFAULT' => 'es',
            'CLI' => 'es',
        ]);

        $this->plugin->process($this->io->reveal(), $config);

        $this->assertEquals([
            'DEFAULT' => 'es',
            'CLI' => 'es',
        ], $config->getLanguage());
        $ask->shouldHaveBeenCalledTimes(1);
    }
}
