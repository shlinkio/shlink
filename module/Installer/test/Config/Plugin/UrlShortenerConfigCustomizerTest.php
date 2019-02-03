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
    /** @var UrlShortenerConfigCustomizer */
    private $plugin;
    /** @var ObjectProphecy */
    private $io;

    public function setUp()
    {
        $this->io = $this->prophesize(SymfonyStyle::class);
        $this->io->title(Argument::any())->willReturn(null);
        $this->plugin = new UrlShortenerConfigCustomizer(function () {
            return 'the_chars';
        });
    }

    /**
     * @test
     */
    public function configIsRequestedToTheUser()
    {
        $choice = $this->io->choice(Argument::cetera())->willReturn('chosen');
        $ask = $this->io->ask(Argument::cetera())->willReturn('asked');
        $confirm = $this->io->confirm(Argument::cetera())->willReturn(true);
        $config = new CustomizableAppConfig();

        $this->plugin->process($this->io->reveal(), $config);

        $this->assertTrue($config->hasUrlShortener());
        $this->assertEquals([
            'SCHEMA' => 'chosen',
            'HOSTNAME' => 'asked',
            'CHARS' => 'the_chars',
            'VALIDATE_URL' => true,
            'ENABLE_NOT_FOUND_REDIRECTION' => true,
            'NOT_FOUND_REDIRECT_TO' => 'asked',
        ], $config->getUrlShortener());
        $ask->shouldHaveBeenCalledTimes(2);
        $choice->shouldHaveBeenCalledOnce();
        $confirm->shouldHaveBeenCalledTimes(2);
    }

    /**
     * @test
     */
    public function onlyMissingOptionsAreAsked()
    {
        $choice = $this->io->choice(Argument::cetera())->willReturn('chosen');
        $ask = $this->io->ask(Argument::cetera())->willReturn('asked');
        $confirm = $this->io->confirm(Argument::cetera())->willReturn(false);
        $config = new CustomizableAppConfig();
        $config->setUrlShortener([
            'SCHEMA' => 'foo',
            'ENABLE_NOT_FOUND_REDIRECTION' => true,
            'NOT_FOUND_REDIRECT_TO' => 'foo',
        ]);

        $this->plugin->process($this->io->reveal(), $config);

        $this->assertEquals([
            'SCHEMA' => 'foo',
            'HOSTNAME' => 'asked',
            'CHARS' => 'the_chars',
            'VALIDATE_URL' => false,
            'ENABLE_NOT_FOUND_REDIRECTION' => true,
            'NOT_FOUND_REDIRECT_TO' => 'foo',
        ], $config->getUrlShortener());
        $choice->shouldNotHaveBeenCalled();
        $ask->shouldHaveBeenCalledOnce();
        $confirm->shouldHaveBeenCalledOnce();
    }

    /**
     * @test
     */
    public function noQuestionsAskedIfImportedConfigContainsEverything()
    {
        $choice = $this->io->choice(Argument::cetera())->willReturn('chosen');
        $ask = $this->io->ask(Argument::cetera())->willReturn('asked');
        $confirm = $this->io->confirm(Argument::cetera())->willReturn(false);

        $config = new CustomizableAppConfig();
        $config->setUrlShortener([
            'SCHEMA' => 'foo',
            'HOSTNAME' => 'foo',
            'CHARS' => 'foo',
            'VALIDATE_URL' => true,
            'ENABLE_NOT_FOUND_REDIRECTION' => true,
            'NOT_FOUND_REDIRECT_TO' => 'foo',
        ]);

        $this->plugin->process($this->io->reveal(), $config);

        $this->assertEquals([
            'SCHEMA' => 'foo',
            'HOSTNAME' => 'foo',
            'CHARS' => 'foo',
            'VALIDATE_URL' => true,
            'ENABLE_NOT_FOUND_REDIRECTION' => true,
            'NOT_FOUND_REDIRECT_TO' => 'foo',
        ], $config->getUrlShortener());
        $choice->shouldNotHaveBeenCalled();
        $ask->shouldNotHaveBeenCalled();
        $confirm->shouldNotHaveBeenCalled();
    }

    /**
     * @test
     */
    public function redirectUrlOptionIsNotAskedIfAnswerToPreviousQuestionIsNo()
    {
        $ask = $this->io->ask(Argument::cetera())->willReturn('asked');
        $confirm = $this->io->confirm(Argument::cetera())->willReturn(false);

        $config = new CustomizableAppConfig();
        $config->setUrlShortener([
            'SCHEMA' => 'foo',
            'HOSTNAME' => 'foo',
            'CHARS' => 'foo',
            'VALIDATE_URL' => true,
        ]);

        $this->plugin->process($this->io->reveal(), $config);

        $this->assertTrue($config->hasUrlShortener());
        $this->assertEquals([
            'SCHEMA' => 'foo',
            'HOSTNAME' => 'foo',
            'CHARS' => 'foo',
            'VALIDATE_URL' => true,
            'ENABLE_NOT_FOUND_REDIRECTION' => false,
        ], $config->getUrlShortener());
        $ask->shouldNotHaveBeenCalled();
        $confirm->shouldHaveBeenCalledOnce();
    }
}
