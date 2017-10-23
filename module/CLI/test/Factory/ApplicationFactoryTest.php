<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Factory;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\CLI\Factory\ApplicationFactory;
use Shlinkio\Shlink\Core\Options\AppOptions;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Zend\I18n\Translator\Translator;
use Zend\ServiceManager\ServiceManager;

class ApplicationFactoryTest extends TestCase
{
    /**
     * @var ApplicationFactory
     */
    protected $factory;

    public function setUp()
    {
        $this->factory = new ApplicationFactory();
    }

    /**
     * @test
     */
    public function serviceIsCreated()
    {
        $instance = $this->factory->__invoke($this->createServiceManager(), '');
        $this->assertInstanceOf(Application::class, $instance);
    }

    /**
     * @test
     */
    public function allCommandsWhichAreServicesAreAdded()
    {
        $sm = $this->createServiceManager([
            'commands' => [
                'foo',
                'bar',
                'baz',
            ],
        ]);
        $sm->setService('foo', $this->prophesize(Command::class)->reveal());
        $sm->setService('baz', $this->prophesize(Command::class)->reveal());

        /** @var Application $instance */
        $instance = $this->factory->__invoke($sm, '');
        $this->assertInstanceOf(Application::class, $instance);
        $this->assertCount(2, $instance->all());
    }

    protected function createServiceManager($config = [])
    {
        return new ServiceManager(['services' => [
            'config' => [
                'cli' => array_merge($config, ['locale' => 'en']),
            ],
            AppOptions::class => new AppOptions(),
            Translator::class => Translator::factory([]),
        ]]);
    }
}
