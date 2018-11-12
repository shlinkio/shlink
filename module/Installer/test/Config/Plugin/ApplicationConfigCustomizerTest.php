<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Installer\Config\Plugin;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Installer\Config\Plugin\ApplicationConfigCustomizer;
use Shlinkio\Shlink\Installer\Exception\InvalidConfigOptionException;
use Shlinkio\Shlink\Installer\Model\CustomizableAppConfig;
use Symfony\Component\Console\Style\SymfonyStyle;
use function array_shift;
use function strpos;

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
        $ask = $this->io->ask(Argument::cetera())->willReturn('asked');
        $confirm = $this->io->confirm(Argument::cetera())->willReturn(false);

        $config = new CustomizableAppConfig();

        $this->plugin->process($this->io->reveal(), $config);

        $this->assertTrue($config->hasApp());
        $this->assertEquals([
            'SECRET' => 'asked',
            'DISABLE_TRACK_PARAM' => 'asked',
            'CHECK_VISITS_THRESHOLD' => false,
        ], $config->getApp());
        $ask->shouldHaveBeenCalledTimes(2);
        $confirm->shouldHaveBeenCalledOnce();
    }

    /**
     * @test
     */
    public function visitsThresholdIsRequestedIfCheckIsEnabled()
    {
        $ask = $this->io->ask(Argument::cetera())->will(function (array $args) {
            $message = array_shift($args);
            return strpos($message, 'What is the amount of visits') === 0 ? 20 : 'asked';
        });
        $confirm = $this->io->confirm(Argument::cetera())->willReturn(true);

        $config = new CustomizableAppConfig();

        $this->plugin->process($this->io->reveal(), $config);

        $this->assertTrue($config->hasApp());
        $this->assertEquals([
            'SECRET' => 'asked',
            'DISABLE_TRACK_PARAM' => 'asked',
            'CHECK_VISITS_THRESHOLD' => true,
            'VISITS_THRESHOLD' => 20,
        ], $config->getApp());
        $ask->shouldHaveBeenCalledTimes(3);
        $confirm->shouldHaveBeenCalledOnce();
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
            'CHECK_VISITS_THRESHOLD' => true,
            'VISITS_THRESHOLD' => 20,
        ]);

        $this->plugin->process($this->io->reveal(), $config);

        $this->assertEquals([
            'SECRET' => 'foo',
            'DISABLE_TRACK_PARAM' => 'disable_param',
            'CHECK_VISITS_THRESHOLD' => true,
            'VISITS_THRESHOLD' => 20,
        ], $config->getApp());
        $ask->shouldHaveBeenCalledOnce();
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
            'CHECK_VISITS_THRESHOLD' => true,
            'VISITS_THRESHOLD' => 20,
        ]);

        $this->plugin->process($this->io->reveal(), $config);

        $this->assertEquals([
            'SECRET' => 'foo',
            'DISABLE_TRACK_PARAM' => 'the_new_secret',
            'CHECK_VISITS_THRESHOLD' => true,
            'VISITS_THRESHOLD' => 20,
        ], $config->getApp());
        $ask->shouldNotHaveBeenCalled();
    }

    /**
     * @test
     * @dataProvider provideInvalidValues
     * @param mixed $value
     */
    public function validateVisitsThresholdThrowsExceptionWhenProvidedValueIsInvalid($value)
    {
        $this->expectException(InvalidConfigOptionException::class);
        $this->plugin->validateVisitsThreshold($value);
    }

    public function provideInvalidValues(): array
    {
        return [
            'string' => ['foo'],
            'empty string' => [''],
            'negative number' => [-5],
            'negative number as string' => ['-5'],
            'zero' => [0],
            'zero as string' => ['0'],
        ];
    }

    /**
     * @test
     * @dataProvider provideValidValues
     * @param mixed $value
     */
    public function validateVisitsThresholdCastsToIntWhenProvidedValueIsValid($value, int $expected)
    {
        $this->assertEquals($expected, $this->plugin->validateVisitsThreshold($value));
    }

    public function provideValidValues(): array
    {
        return [
            'positive as string' => ['20', 20],
            'positive as integer' => [5, 5],
            'one as string' => ['1', 1],
            'one as integer' => [1, 1],
        ];
    }
}
