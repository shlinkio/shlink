<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

trait CliTestUtilsTrait
{
    use ProphecyTrait;

    /**
     * @return ObjectProphecy|Command
     */
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

    private function testerForCommand(Command $mainCommand, Command ...$extraCommands): CommandTester
    {
        $app = new Application();
        $app->add($mainCommand);
        foreach ($extraCommands as $command) {
            $app->add($command);
        }

        return new CommandTester($mainCommand);
    }
}
