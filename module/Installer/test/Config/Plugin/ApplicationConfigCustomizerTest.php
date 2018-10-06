<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Installer\Config\Plugin;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Installer\Config\Plugin\ApplicationConfigCustomizer;
use Shlinkio\Shlink\Installer\Model\CustomizableAppConfig;
use Symfony\Component\Console\Style\SymfonyStyle;

class ApplicationConfigCustomizerTest extends TestCase
{
    /**
     * @var ApplicationConfigCustomizer
     */
    private $plugin;
    /**
     * @var ObjectProphecy
     */
    private $io;

    public function setUp()
    {
        $this->io = $this->prophesize(SymfonyStyle::class);
        $this->io->title(Argument::any())->willReturn(null);

        $this->plugin = new ApplicationConfigCustomizer();
    }

    /**
     * @test
     */
    public function configIsRequestedToTheUser()
    {
        $ask = $this->io->ask(Argument::cetera())->willReturn('the_secret');
        $config = new CustomizableAppConfig();

        $this->plugin->process($this->io->reveal(), $config);

        $this->assertTrue($config->hasApp());
        $this->assertEquals([
            'SECRET' => 'the_secret',
            'DISABLE_TRACK_PARAM' => 'the_secret',
        ], $config->getApp());
        $ask->shouldHaveBeenCalledTimes(2);
    }

    /**
     * @test
     */
    public function onlyMissingOptionsAreAsked()
    {
        $ask = $this->io->ask(Argument::cetera())->willReturn('disable_param');
        $config = new CustomizableAppConfig();
        $config->setApp([
            'SECRET' => 'foo',
        ]);

        $this->plugin->process($this->io->reveal(), $config);

        $this->assertEquals([
            'SECRET' => 'foo',
            'DISABLE_TRACK_PARAM' => 'disable_param',
        ], $config->getApp());
        $ask->shouldHaveBeenCalledTimes(1);
    }

    /**
     * @test
     */
    public function noQuestionsAskedIfImportedConfigContainsEverything()
    {
        $ask = $this->io->ask(Argument::cetera())->willReturn('the_new_secret');

        $config = new CustomizableAppConfig();
        $config->setApp([
            'SECRET' => 'foo',
            'DISABLE_TRACK_PARAM' => 'the_new_secret',
        ]);

        $this->plugin->process($this->io->reveal(), $config);

        $this->assertEquals([
            'SECRET' => 'foo',
            'DISABLE_TRACK_PARAM' => 'the_new_secret',
        ], $config->getApp());
        $ask->shouldNotHaveBeenCalled();
    }
}
