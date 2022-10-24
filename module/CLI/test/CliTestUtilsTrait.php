<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Tester\CommandTester;

trait CliTestUtilsTrait
{
    private function createCommandMock(string $name): MockObject & Command
    {
        $command = $this->createMock(Command::class);
        $command->method('getName')->willReturn($name);
        $command->method('isEnabled')->willReturn(true);
        $command->method('getAliases')->willReturn([]);
        $command->method('getDefinition')->willReturn(new InputDefinition());
        $command->method('setApplication')->with(Assert::isInstanceOf(Application::class));

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
