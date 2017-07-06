<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Install\Plugin;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Install\Plugin\UrlShortenerConfigCustomizerPlugin;
use Shlinkio\Shlink\CLI\Model\CustomizableAppConfig;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class UrlShortenerConfigCustomizerPluginTest extends TestCase
{
    /**
     * @var UrlShortenerConfigCustomizerPlugin
     */
    private $plugin;
    /**
     * @var ObjectProphecy
     */
    private $questionHelper;

    public function setUp()
    {
        $this->questionHelper = $this->prophesize(QuestionHelper::class);
        $this->plugin = new UrlShortenerConfigCustomizerPlugin($this->questionHelper->reveal());
    }

    /**
     * @test
     */
    public function configIsRequestedToTheUser()
    {
        /** @var MethodProphecy $askSecret */
        $askSecret = $this->questionHelper->ask(Argument::cetera())->willReturn('something');
        $config = new CustomizableAppConfig();

        $this->plugin->process(new ArrayInput([]), new NullOutput(), $config);

        $this->assertTrue($config->hasUrlShortener());
        $this->assertEquals([
            'SCHEMA' => 'something',
            'HOSTNAME' => 'something',
            'CHARS' => 'something',
        ], $config->getUrlShortener());
        $askSecret->shouldHaveBeenCalledTimes(3);
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
        ]);

        $this->plugin->process(new ArrayInput([]), new NullOutput(), $config);

        $this->assertEquals([
            'SCHEMA' => 'foo',
            'HOSTNAME' => 'foo',
            'CHARS' => 'foo',
        ], $config->getUrlShortener());
        $ask->shouldHaveBeenCalledTimes(4);
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
        ]);

        $this->plugin->process(new ArrayInput([]), new NullOutput(), $config);

        $this->assertEquals([
            'SCHEMA' => 'foo',
            'HOSTNAME' => 'foo',
            'CHARS' => 'foo',
        ], $config->getUrlShortener());
        $ask->shouldHaveBeenCalledTimes(1);
    }
}
