<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Installer\Config\Plugin;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Installer\Config\Plugin\UrlShortenerConfigCustomizer;
use Shlinkio\Shlink\Installer\Model\CustomizableAppConfig;
use Symfony\Component\Console\Style\SymfonyStyle;

class UrlShortenerConfigCustomizerTest extends TestCase
{
    /**
     * @var UrlShortenerConfigCustomizer
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
        $this->plugin = new UrlShortenerConfigCustomizer();
    }

    /**
     * @test
     */
    public function configIsRequestedToTheUser()
    {
        $choice = $this->io->choice(Argument::cetera())->willReturn('something');
        $ask = $this->io->ask(Argument::cetera())->willReturn('something');
        $confirm = $this->io->confirm(Argument::cetera())->willReturn(true);
        $config = new CustomizableAppConfig();

        $this->plugin->process($this->io->reveal(), $config);

        $this->assertTrue($config->hasUrlShortener());
        $this->assertEquals([
            'SCHEMA' => 'something',
            'HOSTNAME' => 'something',
            'CHARS' => 'something',
            'VALIDATE_URL' => true,
        ], $config->getUrlShortener());
        $ask->shouldHaveBeenCalledTimes(2);
        $choice->shouldHaveBeenCalledTimes(1);
        $confirm->shouldHaveBeenCalledTimes(1);
    }

    /**
     * @test
     */
    public function overwriteIsRequestedIfValueIsAlreadySet()
    {
        $choice = $this->io->choice(Argument::cetera())->willReturn('foo');
        $ask = $this->io->ask(Argument::cetera())->willReturn('foo');
        $confirm = $this->io->confirm(Argument::cetera())->willReturn(false);
        $config = new CustomizableAppConfig();
        $config->setUrlShortener([
            'SCHEMA' => 'bar',
            'HOSTNAME' => 'bar',
            'CHARS' => 'bar',
            'VALIDATE_URL' => true,
        ]);

        $this->plugin->process($this->io->reveal(), $config);

        $this->assertEquals([
            'SCHEMA' => 'foo',
            'HOSTNAME' => 'foo',
            'CHARS' => 'foo',
            'VALIDATE_URL' => false,
        ], $config->getUrlShortener());
        $ask->shouldHaveBeenCalledTimes(2);
        $choice->shouldHaveBeenCalledTimes(1);
        $confirm->shouldHaveBeenCalledTimes(2);
    }

    /**
     * @test
     */
    public function existingValueIsKeptIfRequested()
    {
        $confirm = $this->io->confirm(Argument::cetera())->willReturn(true);

        $config = new CustomizableAppConfig();
        $config->setUrlShortener([
            'SCHEMA' => 'foo',
            'HOSTNAME' => 'foo',
            'CHARS' => 'foo',
            'VALIDATE_URL' => 'foo',
        ]);

        $this->plugin->process($this->io->reveal(), $config);

        $this->assertEquals([
            'SCHEMA' => 'foo',
            'HOSTNAME' => 'foo',
            'CHARS' => 'foo',
            'VALIDATE_URL' => 'foo',
        ], $config->getUrlShortener());
        $confirm->shouldHaveBeenCalledTimes(1);
    }
}
