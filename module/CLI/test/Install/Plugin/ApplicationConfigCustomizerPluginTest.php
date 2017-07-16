<?php
namespace ShlinkioTest\Shlink\CLI\Install\Plugin;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Install\Plugin\ApplicationConfigCustomizerPlugin;
use Shlinkio\Shlink\CLI\Model\CustomizableAppConfig;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class ApplicationConfigCustomizerPluginTest extends TestCase
{
    /**
     * @var ApplicationConfigCustomizerPlugin
     */
    private $plugin;
    /**
     * @var ObjectProphecy
     */
    private $questionHelper;

    public function setUp()
    {
        $this->questionHelper = $this->prophesize(QuestionHelper::class);
        $this->plugin = new ApplicationConfigCustomizerPlugin($this->questionHelper->reveal());
    }

    /**
     * @test
     */
    public function configIsRequestedToTheUser()
    {
        /** @var MethodProphecy $askSecret */
        $askSecret = $this->questionHelper->ask(Argument::cetera())->willReturn('the_secret');
        $config = new CustomizableAppConfig();

        $this->plugin->process(new ArrayInput([]), new NullOutput(), $config);

        $this->assertTrue($config->hasApp());
        $this->assertEquals([
            'SECRET' => 'the_secret',
        ], $config->getApp());
        $askSecret->shouldHaveBeenCalledTimes(1);
    }

    /**
     * @test
     */
    public function overwriteIsRequestedIfValueIsAlreadySet()
    {
        /** @var MethodProphecy $ask */
        $ask = $this->questionHelper->ask(Argument::cetera())->will(function (array $args) {
            $last = array_pop($args);
            return $last instanceof ConfirmationQuestion ? false : 'the_new_secret';
        });
        $config = new CustomizableAppConfig();
        $config->setApp([
            'SECRET' => 'foo',
        ]);

        $this->plugin->process(new ArrayInput([]), new NullOutput(), $config);

        $this->assertEquals([
            'SECRET' => 'the_new_secret',
        ], $config->getApp());
        $ask->shouldHaveBeenCalledTimes(2);
    }

    /**
     * @test
     */
    public function existingValueIsKeptIfRequested()
    {
        /** @var MethodProphecy $ask */
        $ask = $this->questionHelper->ask(Argument::cetera())->willReturn(true);

        $config = new CustomizableAppConfig();
        $config->setApp([
            'SECRET' => 'foo',
        ]);

        $this->plugin->process(new ArrayInput([]), new NullOutput(), $config);

        $this->assertEquals([
            'SECRET' => 'foo',
        ], $config->getApp());
        $ask->shouldHaveBeenCalledTimes(1);
    }
}
