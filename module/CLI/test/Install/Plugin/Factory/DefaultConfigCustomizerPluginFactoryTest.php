<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Install\Plugin\Factory;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\CLI\Install\Plugin\ApplicationConfigCustomizerPlugin;
use Shlinkio\Shlink\CLI\Install\Plugin\Factory\DefaultConfigCustomizerPluginFactory;
use Shlinkio\Shlink\CLI\Install\Plugin\LanguageConfigCustomizerPlugin;
use Symfony\Component\Console\Helper\QuestionHelper;
use Zend\ServiceManager\ServiceManager;

class DefaultConfigCustomizerPluginFactoryTest extends TestCase
{
    /**
     * @var DefaultConfigCustomizerPluginFactory
     */
    protected $factory;

    public function setUp()
    {
        $this->factory = new DefaultConfigCustomizerPluginFactory();
    }

    /**
     * @test
     */
    public function createsProperService()
    {
        $instance = $this->factory->__invoke(new ServiceManager(['services' => [
            QuestionHelper::class => $this->prophesize(QuestionHelper::class)->reveal(),
        ]]), ApplicationConfigCustomizerPlugin::class);
        $this->assertInstanceOf(ApplicationConfigCustomizerPlugin::class, $instance);

        $instance = $this->factory->__invoke(new ServiceManager(['services' => [
            QuestionHelper::class => $this->prophesize(QuestionHelper::class)->reveal(),
        ]]), LanguageConfigCustomizerPlugin::class);
        $this->assertInstanceOf(LanguageConfigCustomizerPlugin::class, $instance);
    }
}
