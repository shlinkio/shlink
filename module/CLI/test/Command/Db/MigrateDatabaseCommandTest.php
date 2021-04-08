<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Db;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\Db\MigrateDatabaseCommand;
use Shlinkio\Shlink\CLI\Util\ProcessRunnerInterface;
use ShlinkioTest\Shlink\CLI\CliTestUtilsTrait;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Process\PhpExecutableFinder;

class MigrateDatabaseCommandTest extends TestCase
{
    use CliTestUtilsTrait;

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

        $this->processHelper = $this->prophesize(ProcessRunnerInterface::class);

        $command = new MigrateDatabaseCommand(
            $locker->reveal(),
            $this->processHelper->reveal(),
            $phpExecutableFinder->reveal(),
        );
        $this->commandTester = $this->testerForCommand($command);
    }

    /** @test */
    public function migrationsCommandIsRunWithProperVerbosity(): void
    {
        $runCommand = $this->processHelper->run(Argument::type(OutputInterface::class), [
            '/usr/local/bin/php',
            MigrateDatabaseCommand::DOCTRINE_MIGRATIONS_SCRIPT,
            MigrateDatabaseCommand::DOCTRINE_MIGRATE_COMMAND,
            '--no-interaction',
        ]);

        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('Migrating database...', $output);
        self::assertStringContainsString('Database properly migrated!', $output);
        $runCommand->shouldHaveBeenCalledOnce();
    }
}
