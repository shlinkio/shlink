<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Db;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\CLI\Command\Db\CreateDatabaseCommand;
use Shlinkio\Shlink\CLI\Util\ProcessRunnerInterface;
use ShlinkioTest\Shlink\CLI\Util\CliTestUtils;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;

class CreateDatabaseCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private MockObject & ProcessRunnerInterface $processHelper;
    private MockObject & Connection $regularConn;
    private Stub & ClassMetadataFactory $metadataFactory;
    /** @var MockObject&AbstractSchemaManager<SQLitePlatform> */
    private MockObject & AbstractSchemaManager $schemaManager;
    private Stub & Driver $driver;

    protected function setUp(): void
    {
        $locker = $this->createStub(LockFactory::class);
        $lock = $this->createStub(SharedLockInterface::class);
        $lock->method('acquire')->willReturn(true);
        $locker->method('createLock')->willReturn($lock);

        $this->processHelper = $this->createMock(ProcessRunnerInterface::class);
        $this->schemaManager = $this->createMock(AbstractSchemaManager::class);

        $this->regularConn = $this->createMock(Connection::class);
        $this->regularConn->method('createSchemaManager')->willReturn($this->schemaManager);
        $this->driver = $this->createStub(Driver::class);
        $this->regularConn->method('getDriver')->willReturn($this->driver);

        $this->metadataFactory = $this->createStub(ClassMetadataFactory::class);
        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($this->regularConn);
        $em->method('getMetadataFactory')->willReturn($this->metadataFactory);

        $noDbNameConn = $this->createStub(Connection::class);
        $noDbNameConn->method('createSchemaManager')->willReturn($this->schemaManager);

        $command = new CreateDatabaseCommand($locker, $this->processHelper, $em, $noDbNameConn);
        $this->commandTester = CliTestUtils::testerForCommand($command);
    }

    #[Test]
    public function successMessageIsPrintedIfDatabaseAlreadyExists(): void
    {
        $this->regularConn->expects($this->never())->method('getParams');

        $metadataMock = $this->createMock(ClassMetadata::class);
        $metadataMock->expects($this->once())->method('getTableName')->willReturn('foo_table');
        $this->metadataFactory->method('getAllMetadata')->willReturn([$metadataMock]);
        $this->schemaManager->expects($this->never())->method('createDatabase');
        $this->schemaManager->expects($this->once())->method('listTableNames')->willReturn(['foo_table', 'bar_table']);
        $this->processHelper->expects($this->never())->method('run');

        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('Database already exists. Run "db:migrate" command', $output);
    }

    #[Test]
    public function databaseIsCreatedIfItDoesNotExist(): void
    {
        $this->driver->method('getDatabasePlatform')->willReturn($this->createStub(AbstractPlatform::class));

        $shlinkDatabase = 'shlink_database';
        $this->regularConn->expects($this->once())->method('getParams')->willReturn(['dbname' => $shlinkDatabase]);
        $this->metadataFactory->method('getAllMetadata')->willReturn([]);
        $this->schemaManager->expects($this->once())->method('createDatabase')->with($shlinkDatabase);
        $this->schemaManager->expects($this->once())->method('listTableNames')->willThrowException(new Exception(''));
        $this->processHelper->expects($this->once())->method('run');

        $this->commandTester->execute([]);
    }

    #[Test, DataProvider('provideEmptyDatabase')]
    public function tablesAreCreatedIfDatabaseIsEmpty(array $tables): void
    {
        $this->regularConn->expects($this->never())->method('getParams');
        $this->driver->method('getDatabasePlatform')->willReturn($this->createStub(AbstractPlatform::class));

        $metadata = $this->createStub(ClassMetadata::class);
        $metadata->method('getTableName')->willReturn('shlink_table');
        $this->metadataFactory->method('getAllMetadata')->willReturn([$metadata]);
        $this->schemaManager->expects($this->never())->method('createDatabase');
        $this->schemaManager->expects($this->once())->method('listTableNames')->willReturn($tables);
        $this->processHelper->expects($this->once())->method('run')->with($this->isInstanceOf(OutputInterface::class), [
            CreateDatabaseCommand::SCRIPT,
            CreateDatabaseCommand::COMMAND,
            '--no-interaction',
        ]);

        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('Creating database tables...', $output);
        self::assertStringContainsString('Database properly created!', $output);
    }

    public static function provideEmptyDatabase(): iterable
    {
        yield 'no tables' => [[]];
        yield 'migrations table' => [['non_shlink_table']];
    }
}
