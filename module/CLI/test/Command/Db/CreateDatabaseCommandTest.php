<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Db;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\CLI\Command\Db\CreateDatabaseCommand;
use Shlinkio\Shlink\CLI\Util\ProcessRunnerInterface;
use ShlinkioTest\Shlink\CLI\CliTestUtilsTrait;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Process\PhpExecutableFinder;

use const Shlinkio\Shlink\MIGRATIONS_TABLE;

class CreateDatabaseCommandTest extends TestCase
{
    use CliTestUtilsTrait;

    private CommandTester $commandTester;
    private MockObject & ProcessRunnerInterface $processHelper;
    private MockObject & Connection $regularConn;
    private MockObject & AbstractSchemaManager $schemaManager;
    private MockObject & Driver $driver;

    protected function setUp(): void
    {
        $locker = $this->createMock(LockFactory::class);
        $lock = $this->createMock(LockInterface::class);
        $lock->method('acquire')->withAnyParameters()->willReturn(true);
        $locker->method('createLock')->withAnyParameters()->willReturn($lock);

        $phpExecutableFinder = $this->createMock(PhpExecutableFinder::class);
        $phpExecutableFinder->method('find')->with($this->isFalse())->willReturn('/usr/local/bin/php');

        $this->processHelper = $this->createMock(ProcessRunnerInterface::class);
        $this->schemaManager = $this->createMock(AbstractSchemaManager::class);

        $this->regularConn = $this->createMock(Connection::class);
        $this->regularConn->method('createSchemaManager')->willReturn($this->schemaManager);
        $this->driver = $this->createMock(Driver::class);
        $this->regularConn->method('getDriver')->willReturn($this->driver);
        $noDbNameConn = $this->createMock(Connection::class);
        $noDbNameConn->method('createSchemaManager')->withAnyParameters()->willReturn($this->schemaManager);

        $command = new CreateDatabaseCommand(
            $locker,
            $this->processHelper,
            $phpExecutableFinder,
            $this->regularConn,
            $noDbNameConn,
        );

        $this->commandTester = $this->testerForCommand($command);
    }

    /** @test */
    public function successMessageIsPrintedIfDatabaseAlreadyExists(): void
    {
        $shlinkDatabase = 'shlink_database';
        $this->regularConn->expects($this->once())->method('getParams')->willReturn(['dbname' => $shlinkDatabase]);
        $this->schemaManager->expects($this->once())->method('listDatabases')->willReturn(
            ['foo', $shlinkDatabase, 'bar'],
        );
        $this->schemaManager->expects($this->never())->method('createDatabase');
        $this->schemaManager->expects($this->once())->method('listTableNames')->willReturn(['foo_table', 'bar_table']);
        $this->driver->method('getDatabasePlatform')->willReturn($this->createMock(AbstractPlatform::class));

        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('Database already exists. Run "db:migrate" command', $output);
    }

    /** @test */
    public function databaseIsCreatedIfItDoesNotExist(): void
    {
        $shlinkDatabase = 'shlink_database';
        $this->regularConn->expects($this->once())->method('getParams')->willReturn(['dbname' => $shlinkDatabase]);
        $this->schemaManager->expects($this->once())->method('listDatabases')->willReturn(['foo', 'bar']);
        $this->schemaManager->expects($this->once())->method('createDatabase')->with($shlinkDatabase);
        $this->schemaManager->expects($this->once())->method('listTableNames')->willReturn(
            ['foo_table', 'bar_table', MIGRATIONS_TABLE],
        );
        $this->driver->method('getDatabasePlatform')->willReturn($this->createMock(AbstractPlatform::class));

        $this->commandTester->execute([]);
    }

    /**
     * @test
     * @dataProvider provideEmptyDatabase
     */
    public function tablesAreCreatedIfDatabaseIsEmpty(array $tables): void
    {
        $shlinkDatabase = 'shlink_database';
        $this->regularConn->expects($this->once())->method('getParams')->willReturn(['dbname' => $shlinkDatabase]);
        $this->schemaManager->expects($this->once())->method('listDatabases')->willReturn(
            ['foo', $shlinkDatabase, 'bar'],
        );
        $this->schemaManager->expects($this->never())->method('createDatabase');
        $this->schemaManager->expects($this->once())->method('listTableNames')->willReturn($tables);
        $this->processHelper->expects($this->once())->method('run')->with($this->isInstanceOf(OutputInterface::class), [
            '/usr/local/bin/php',
            CreateDatabaseCommand::DOCTRINE_SCRIPT,
            CreateDatabaseCommand::DOCTRINE_CREATE_SCHEMA_COMMAND,
            '--no-interaction',
        ]);
        $this->driver->method('getDatabasePlatform')->willReturn($this->createMock(AbstractPlatform::class));

        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('Creating database tables...', $output);
        self::assertStringContainsString('Database properly created!', $output);
    }

    public static function provideEmptyDatabase(): iterable
    {
        yield 'no tables' => [[]];
        yield 'migrations table' => [[MIGRATIONS_TABLE]];
    }

    /** @test */
    public function databaseCheckIsSkippedForSqlite(): void
    {
        $this->driver->method('getDatabasePlatform')->willReturn($this->createMock(SqlitePlatform::class));

        $this->regularConn->expects($this->never())->method('getParams');
        $this->schemaManager->expects($this->never())->method('listDatabases');
        $this->schemaManager->expects($this->never())->method('createDatabase');
        $this->schemaManager->expects($this->once())->method('listTableNames')->willReturn(['foo_table', 'bar_table']);

        $this->commandTester->execute([]);
    }
}
