<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Install\Plugin;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Install\Plugin\UrlShortenerConfigCustomizer;
use Shlinkio\Shlink\CLI\Model\CustomizableAppConfig;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class UrlShortenerConfigCustomizerPluginTest extends TestCase
{
    /**
     * @var UrlShortenerConfigCustomizer
     */
    private $plugin;
    /**
     * @var ObjectProphecy
     */
    private $questionHelper;

    public function setUp()
    {
        $this->questionHelper = $this->prophesize(QuestionHelper::class);
        $this->plugin = new UrlShortenerConfigCustomizer($this->questionHelper->reveal());
    }

    /**
     * @test
     */
    public function configIsRequestedToTheUser()
    {
        /** @var MethodProphecy $askSecret */
        $askSecret = $this->questionHelper->ask(Argument::cetera())->willReturn('something');
        $config = new CustomizableAppConfig();

        $this->plugin->process(new SymfonyStyle(new ArrayInput([]), new NullOutput()), $config);

        $this->assertTrue($config->hasUrlShortener());
        $this->assertEquals([
            'SCHEMA' => 'something',
            'HOSTNAME' => 'something',
            'CHARS' => 'something',
            'VALIDATE_URL' => 'something',
        ], $config->getUrlShortener());
        $askSecret->shouldHaveBeenCalledTimes(4);
    }

    /**
     * @test
     */
    public function overwriteIsRequestedIfValueIsAlreadySet()
    {
        /** @var MethodProphecy $ask */
        $ask = $this->questionHelper->ask(Argument::cetera())->will(function (array $args) {
            $last = array_pop($args);
            return $last instanceof ConfirmationQuestion ? false : 'foo';
        });
        $config = new CustomizableAppConfig();
        $config->setUrlShortener([
            'SCHEMA' => 'bar',
            'HOSTNAME' => 'bar',
            'CHARS' => 'bar',
            'VALIDATE_URL' => 'bar',
        ]);

        $this->plugin->process(new SymfonyStyle(new ArrayInput([]), new NullOutput()), $config);

        $this->assertEquals([
            'SCHEMA' => 'foo',
            'HOSTNAME' => 'foo',
            'CHARS' => 'foo',
            'VALIDATE_URL' => false,
        ], $config->getUrlShortener());
        $ask->shouldHaveBeenCalledTimes(5);
    }

    /**
     * @test
     */
    public function existingValueIsKeptIfRequested()
    {
        /** @var MethodProphecy $ask */
        $ask = $this->questionHelper->ask(Argument::cetera())->willReturn(true);

        $config = new CustomizableAppConfig();
        $config->setUrlShortener([
            'SCHEMA' => 'foo',
            'HOSTNAME' => 'foo',
            'CHARS' => 'foo',
            'VALIDATE_URL' => 'foo',
        ]);

        $this->plugin->process(new SymfonyStyle(new ArrayInput([]), new NullOutput()), $config);

        $this->assertEquals([
            'SCHEMA' => 'foo',
            'HOSTNAME' => 'foo',
            'CHARS' => 'foo',
            'VALIDATE_URL' => 'foo',
        ], $config->getUrlShortener());
        $ask->shouldHaveBeenCalledTimes(1);
    }
}
