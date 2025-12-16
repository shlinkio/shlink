<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Db;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\CLI\Command\Db\MigrateDatabaseCommand;
use Shlinkio\Shlink\CLI\Util\ProcessRunnerInterface;
use ShlinkioTest\Shlink\CLI\Util\CliTestUtils;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;

class MigrateDatabaseCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private MockObject & ProcessRunnerInterface $processHelper;

    protected function setUp(): void
    {
        $locker = $this->createStub(LockFactory::class);
        $lock = $this->createStub(SharedLockInterface::class);
        $lock->method('acquire')->willReturn(true);
        $locker->method('createLock')->willReturn($lock);

        $this->processHelper = $this->createMock(ProcessRunnerInterface::class);

        $command = new MigrateDatabaseCommand($locker, $this->processHelper);
        $this->commandTester = CliTestUtils::testerForCommand($command);
    }

    #[Test]
    public function migrationsCommandIsRunWithProperVerbosity(): void
    {
        $this->processHelper->expects($this->once())->method('run')->with($this->isInstanceOf(OutputInterface::class), [
            MigrateDatabaseCommand::SCRIPT,
            MigrateDatabaseCommand::COMMAND,
            '--no-interaction',
        ]);

        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('Migrating database...', $output);
        self::assertStringContainsString('Database properly migrated!', $output);
    }
}
