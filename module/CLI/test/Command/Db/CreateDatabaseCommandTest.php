<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Db;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\Db\CreateDatabaseCommand;
use Shlinkio\Shlink\CLI\Util\ProcessRunnerInterface;
use ShlinkioTest\Shlink\CLI\CliTestUtilsTrait;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Process\PhpExecutableFinder;

class CreateDatabaseCommandTest extends TestCase
{
    use CliTestUtilsTrait;

    private CommandTester $commandTester;
    private ObjectProphecy $processHelper;
    private ObjectProphecy $regularConn;
    private ObjectProphecy $schemaManager;
    private ObjectProphecy $databasePlatform;

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
        $this->schemaManager = $this->prophesize(AbstractSchemaManager::class);
        $this->databasePlatform = $this->prophesize(AbstractPlatform::class);

        $this->regularConn = $this->prophesize(Connection::class);
        $this->regularConn->getSchemaManager()->willReturn($this->schemaManager->reveal());
        $this->regularConn->getDatabasePlatform()->willReturn($this->databasePlatform->reveal());
        $noDbNameConn = $this->prophesize(Connection::class);
        $noDbNameConn->getSchemaManager()->willReturn($this->schemaManager->reveal());

        $command = new CreateDatabaseCommand(
            $locker->reveal(),
            $this->processHelper->reveal(),
            $phpExecutableFinder->reveal(),
            $this->regularConn->reveal(),
            $noDbNameConn->reveal(),
        );

        $this->commandTester = $this->testerForCommand($command);
    }

    /** @test */
    public function successMessageIsPrintedIfDatabaseAlreadyExists(): void
    {
        $shlinkDatabase = 'shlink_database';
        $getDatabase = $this->regularConn->getDatabase()->willReturn($shlinkDatabase);
        $listDatabases = $this->schemaManager->listDatabases()->willReturn(['foo', $shlinkDatabase, 'bar']);
        $createDatabase = $this->schemaManager->createDatabase($shlinkDatabase)->will(function (): void {
        });
        $listTables = $this->schemaManager->listTableNames()->willReturn(['foo_table', 'bar_table']);

        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('Database already exists. Run "db:migrate" command', $output);
        $getDatabase->shouldHaveBeenCalledOnce();
        $listDatabases->shouldHaveBeenCalledOnce();
        $createDatabase->shouldNotHaveBeenCalled();
        $listTables->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function databaseIsCreatedIfItDoesNotExist(): void
    {
        $shlinkDatabase = 'shlink_database';
        $getDatabase = $this->regularConn->getDatabase()->willReturn($shlinkDatabase);
        $listDatabases = $this->schemaManager->listDatabases()->willReturn(['foo', 'bar']);
        $createDatabase = $this->schemaManager->createDatabase($shlinkDatabase)->will(function (): void {
        });
        $listTables = $this->schemaManager->listTableNames()->willReturn(['foo_table', 'bar_table']);

        $this->commandTester->execute([]);

        $getDatabase->shouldHaveBeenCalledOnce();
        $listDatabases->shouldHaveBeenCalledOnce();
        $createDatabase->shouldHaveBeenCalledOnce();
        $listTables->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function tablesAreCreatedIfDatabaseIsEmpty(): void
    {
        $shlinkDatabase = 'shlink_database';
        $getDatabase = $this->regularConn->getDatabase()->willReturn($shlinkDatabase);
        $listDatabases = $this->schemaManager->listDatabases()->willReturn(['foo', $shlinkDatabase, 'bar']);
        $createDatabase = $this->schemaManager->createDatabase($shlinkDatabase)->will(function (): void {
        });
        $listTables = $this->schemaManager->listTableNames()->willReturn([]);
        $runCommand = $this->processHelper->run(Argument::type(OutputInterface::class), [
            '/usr/local/bin/php',
            CreateDatabaseCommand::DOCTRINE_SCRIPT,
            CreateDatabaseCommand::DOCTRINE_CREATE_SCHEMA_COMMAND,
            '--no-interaction',
        ]);

        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('Creating database tables...', $output);
        self::assertStringContainsString('Database properly created!', $output);
        $getDatabase->shouldHaveBeenCalledOnce();
        $listDatabases->shouldHaveBeenCalledOnce();
        $createDatabase->shouldNotHaveBeenCalled();
        $listTables->shouldHaveBeenCalledOnce();
        $runCommand->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function databaseCheckIsSkippedForSqlite(): void
    {
        $this->databasePlatform->getName()->willReturn('sqlite');

        $shlinkDatabase = 'shlink_database';
        $getDatabase = $this->regularConn->getDatabase()->willReturn($shlinkDatabase);
        $listDatabases = $this->schemaManager->listDatabases()->willReturn(['foo', 'bar']);
        $createDatabase = $this->schemaManager->createDatabase($shlinkDatabase)->will(function (): void {
        });
        $listTables = $this->schemaManager->listTableNames()->willReturn(['foo_table', 'bar_table']);

        $this->commandTester->execute([]);

        $getDatabase->shouldNotHaveBeenCalled();
        $listDatabases->shouldNotHaveBeenCalled();
        $createDatabase->shouldNotHaveBeenCalled();
        $listTables->shouldHaveBeenCalledOnce();
    }
}
