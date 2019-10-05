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
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Lock\Factory as Locker;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Process\PhpExecutableFinder;

class CreateDatabaseCommandTest extends TestCase
{
    /** @var CommandTester */
    private $commandTester;
    /** @var ObjectProphecy */
    private $processHelper;
    /** @var ObjectProphecy */
    private $regularConn;
    /** @var ObjectProphecy */
    private $noDbNameConn;
    /** @var ObjectProphecy */
    private $schemaManager;
    /** @var ObjectProphecy */
    private $databasePlatform;

    public function setUp(): void
    {
        $locker = $this->prophesize(Locker::class);
        $lock = $this->prophesize(LockInterface::class);
        $lock->acquire(Argument::any())->willReturn(true);
        $lock->release()->will(function () {
        });
        $locker->createLock(Argument::cetera())->willReturn($lock->reveal());

        $phpExecutableFinder = $this->prophesize(PhpExecutableFinder::class);
        $phpExecutableFinder->find(false)->willReturn('/usr/local/bin/php');

        $this->processHelper = $this->prophesize(ProcessHelper::class);
        $this->schemaManager = $this->prophesize(AbstractSchemaManager::class);
        $this->databasePlatform = $this->prophesize(AbstractPlatform::class);

        $this->regularConn = $this->prophesize(Connection::class);
        $this->regularConn->getSchemaManager()->willReturn($this->schemaManager->reveal());
        $this->regularConn->getDatabasePlatform()->willReturn($this->databasePlatform->reveal());
        $this->noDbNameConn = $this->prophesize(Connection::class);
        $this->noDbNameConn->getSchemaManager()->willReturn($this->schemaManager->reveal());

        $command = new CreateDatabaseCommand(
            $locker->reveal(),
            $this->processHelper->reveal(),
            $phpExecutableFinder->reveal(),
            $this->regularConn->reveal(),
            $this->noDbNameConn->reveal()
        );
        $app = new Application();
        $app->add($command);

        $this->commandTester = new CommandTester($command);
    }

    /** @test */
    public function successMessageIsPrintedIfDatabaseAlreadyExists(): void
    {
        $shlinkDatabase = 'shlink_database';
        $getDatabase = $this->regularConn->getDatabase()->willReturn($shlinkDatabase);
        $listDatabases = $this->schemaManager->listDatabases()->willReturn(['foo', $shlinkDatabase, 'bar']);
        $createDatabase = $this->schemaManager->createDatabase($shlinkDatabase)->will(function () {
        });
        $listTables = $this->schemaManager->listTableNames()->willReturn(['foo_table', 'bar_table']);

        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Database already exists. Run "db:migrate" command', $output);
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
        $createDatabase = $this->schemaManager->createDatabase($shlinkDatabase)->will(function () {
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
        $createDatabase = $this->schemaManager->createDatabase($shlinkDatabase)->will(function () {
        });
        $listTables = $this->schemaManager->listTableNames()->willReturn([]);
        $runCommand = $this->processHelper->run(Argument::type(OutputInterface::class), [
            '/usr/local/bin/php',
            CreateDatabaseCommand::DOCTRINE_HELPER_SCRIPT,
            CreateDatabaseCommand::DOCTRINE_HELPER_COMMAND,
        ], Argument::cetera());

        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Creating database tables...', $output);
        $this->assertStringContainsString('Database properly created!', $output);
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
        $createDatabase = $this->schemaManager->createDatabase($shlinkDatabase)->will(function () {
        });
        $listTables = $this->schemaManager->listTableNames()->willReturn(['foo_table', 'bar_table']);

        $this->commandTester->execute([]);

        $getDatabase->shouldNotHaveBeenCalled();
        $listDatabases->shouldNotHaveBeenCalled();
        $createDatabase->shouldNotHaveBeenCalled();
        $listTables->shouldHaveBeenCalledOnce();
    }
}
