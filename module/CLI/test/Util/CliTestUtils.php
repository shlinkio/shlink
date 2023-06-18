<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Util;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\Generator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Tester\CommandTester;

class CliTestUtils
{
    public static function createCommandMock(string $name): MockObject & Command
    {
        static $generator = null;

        if ($generator === null) {
            $generator = new Generator();
        }

        $command = $generator->getMock(
            Command::class,
            callOriginalConstructor: false,
            callOriginalClone: false,
            cloneArguments: false,
            allowMockingUnknownTypes: false,
        );
        $command->method('getName')->willReturn($name);
        $command->method('isEnabled')->willReturn(true);
        $command->method('getAliases')->willReturn([]);
        $command->method('getDefinition')->willReturn(new InputDefinition());
        $command->method('setApplication')->with(Assert::isInstanceOf(Application::class));

        return $command;
    }

    public static function testerForCommand(Command $mainCommand, Command ...$extraCommands): CommandTester
    {
        $app = new Application();
        $app->add($mainCommand);
        foreach ($extraCommands as $command) {
            $app->add($command);
        }

        return new CommandTester($mainCommand);
    }
}
