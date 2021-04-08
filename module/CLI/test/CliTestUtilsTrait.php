<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;

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
}
