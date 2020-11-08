<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Factory;

use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Factory\ApplicationFactory;
use Shlinkio\Shlink\Core\Options\AppOptions;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;

class ApplicationFactoryTest extends TestCase
{
    use ProphecyTrait;

    private ApplicationFactory $factory;

    public function setUp(): void
    {
        $this->factory = new ApplicationFactory();
    }

    /** @test */
    public function allCommandsWhichAreServicesAreAdded(): void
    {
        $sm = $this->createServiceManager([
            'commands' => [
                'foo' => 'foo',
                'bar' => 'bar',
                'baz' => 'baz',
            ],
        ]);
        $sm->setService('foo', $this->createCommandMock('foo')->reveal());
        $sm->setService('bar', $this->createCommandMock('bar')->reveal());

        $instance = ($this->factory)($sm);

        self::assertTrue($instance->has('foo'));
        self::assertTrue($instance->has('bar'));
        self::assertFalse($instance->has('baz'));
    }

    private function createServiceManager(array $config = []): ServiceManager
    {
        return new ServiceManager(['services' => [
            'config' => [
                'cli' => $config,
            ],
            AppOptions::class => new AppOptions(),
        ]]);
    }

    private function createCommandMock(string $name): ObjectProphecy
    {
        $command = $this->prophesize(Command::class);
        $command->getName()->willReturn($name);
        $command->getDefinition()->willReturn($name);
        $command->isEnabled()->willReturn(true);
        $command->getAliases()->willReturn([]);
        $command->setApplication(Argument::type(Application::class))->willReturn(function (): void {
        });

        return $command;
    }
}
