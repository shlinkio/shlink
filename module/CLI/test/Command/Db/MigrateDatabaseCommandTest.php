<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Db;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\Db\MigrateDatabaseCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class MigrateDatabaseCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private ObjectProphecy $processHelper;

    public function setUp(): void
    {
        $locker = $this->prophesize(LockFactory::class);
        $lock = $this->prophesize(LockInterface::class);
        $lock->acquire(Argument::any())->willReturn(true);
        $lock->release()->will(function (): void {
        });
        $locker->createLock(Argument::cetera())->willReturn($lock->reveal());

        $phpExecutableFinder = $this->prophesize(PhpExecutableFinder::class);
        $phpExecutableFinder->find(false)->willReturn('/usr/local/bin/php');

        $this->processHelper = $this->prophesize(ProcessHelper::class);

        $command = new MigrateDatabaseCommand(
            $locker->reveal(),
            $this->processHelper->reveal(),
            $phpExecutableFinder->reveal(),
        );
        $app = new Application();
        $app->add($command);

        $this->commandTester = new CommandTester($command);
    }

    /** @test */
    public function migrationsCommandIsRunWithProperVerbosity(): void
    {
        $runCommand = $this->processHelper->mustRun(Argument::type(OutputInterface::class), [
            '/usr/local/bin/php',
            MigrateDatabaseCommand::DOCTRINE_MIGRATIONS_SCRIPT,
            MigrateDatabaseCommand::DOCTRINE_MIGRATE_COMMAND,
            '--no-interaction',
        ], Argument::cetera())->willReturn(new Process([]));

        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('Migrating database...', $output);
        self::assertStringContainsString('Database properly migrated!', $output);
        $runCommand->shouldHaveBeenCalledOnce();
    }
}
