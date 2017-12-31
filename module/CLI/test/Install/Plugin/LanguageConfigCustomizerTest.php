<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Install\Plugin;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Install\Plugin\LanguageConfigCustomizer;
use Shlinkio\Shlink\CLI\Model\CustomizableAppConfig;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Question\ConfirmationQuestion;
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
    protected $questionHelper;

    public function setUp()
    {
        $this->questionHelper = $this->prophesize(QuestionHelper::class);
        $this->plugin = new LanguageConfigCustomizer($this->questionHelper->reveal());
    }

    /**
     * @test
     */
    public function configIsRequestedToTheUser()
    {
        /** @var MethodProphecy $askSecret */
        $askSecret = $this->questionHelper->ask(Argument::cetera())->willReturn('en');
        $config = new CustomizableAppConfig();

        $this->plugin->process(new SymfonyStyle(new ArrayInput([]), new NullOutput()), $config);

        $this->assertTrue($config->hasLanguage());
        $this->assertEquals([
            'DEFAULT' => 'en',
            'CLI' => 'en',
        ], $config->getLanguage());
        $askSecret->shouldHaveBeenCalledTimes(2);
    }

    /**
     * @test
     */
    public function overwriteIsRequestedIfValueIsAlreadySet()
    {
        /** @var MethodProphecy $ask */
        $ask = $this->questionHelper->ask(Argument::cetera())->will(function (array $args) {
            $last = array_pop($args);
            return $last instanceof ConfirmationQuestion ? false : 'es';
        });
        $config = new CustomizableAppConfig();
        $config->setLanguage([
            'DEFAULT' => 'en',
            'CLI' => 'en',
        ]);

        $this->plugin->process(new SymfonyStyle(new ArrayInput([]), new NullOutput()), $config);

        $this->assertEquals([
            'DEFAULT' => 'es',
            'CLI' => 'es',
        ], $config->getLanguage());
        $ask->shouldHaveBeenCalledTimes(3);
    }

    /**
     * @test
     */
    public function existingValueIsKeptIfRequested()
    {
        /** @var MethodProphecy $ask */
        $ask = $this->questionHelper->ask(Argument::cetera())->willReturn(true);

        $config = new CustomizableAppConfig();
        $config->setLanguage([
            'DEFAULT' => 'es',
            'CLI' => 'es',
        ]);

        $this->plugin->process(new SymfonyStyle(new ArrayInput([]), new NullOutput()), $config);

        $this->assertEquals([
            'DEFAULT' => 'es',
            'CLI' => 'es',
        ], $config->getLanguage());
        $ask->shouldHaveBeenCalledTimes(1);
    }
}
