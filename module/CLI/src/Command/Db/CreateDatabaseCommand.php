<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Db;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Shlinkio\Shlink\CLI\Util\ProcessRunnerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Lock\LockFactory;
use Throwable;

use function array_map;
use function Shlinkio\Shlink\Core\ArrayUtils\contains;
use function Shlinkio\Shlink\Core\ArrayUtils\some;

class CreateDatabaseCommand extends AbstractDatabaseCommand
{
    private readonly Connection $regularConn;

    public const string NAME = 'db:create';
    public const string SCRIPT = 'bin/doctrine';
    public const string COMMAND = 'orm:schema-tool:create';

    public function __construct(
        LockFactory $locker,
        private readonly ProcessRunnerInterface $processRunner,
        private readonly EntityManagerInterface $em,
        private readonly Connection $noDbNameConn,
    ) {
        $this->regularConn = $this->em->getConnection();
        parent::__construct($locker);
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setHidden()
            ->setDescription(
                'Creates the database needed for shlink to work. It will do nothing if the database already exists',
            );
    }

    protected function lockedExecute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($this->databaseTablesExist()) {
            $io->success('Database already exists. Run "db:migrate" command to make sure it is up to date.');
            return self::SUCCESS;
        }

        // Create database
        $io->writeln('<fg=blue>Creating database tables...</>');
        $this->processRunner->run($output, [self::SCRIPT, self::COMMAND, '--no-interaction']);
        $io->success('Database properly created!');

        return self::SUCCESS;
    }

    private function databaseTablesExist(): bool
    {
        $existingTables = $this->ensureDatabaseExistsAndGetTables();
        $allMetadata = $this->em->getMetadataFactory()->getAllMetadata();
        $shlinkTables = array_map(static fn (ClassMetadata $metadata) => $metadata->getTableName(), $allMetadata);

        // If at least one of the shlink tables exist, we will consider the database exists somehow.
        // Any other inconsistency will be taken care of by the migrations.
        return some($shlinkTables, static fn (string $shlinkTable) => contains($shlinkTable, $existingTables));
    }

    private function ensureDatabaseExistsAndGetTables(): array
    {
        try {
            // Trying to list tables requires opening a connection to configured database.
            // If it fails, it means it does not exist yet.
            return $this->regularConn->createSchemaManager()->listTableNames();
        } catch (Throwable) {
            // We cannot use getDatabase() to get the database name here, because then the driver will try to connect.
            // Instead, we read from the raw params.
            $shlinkDatabase = $this->regularConn->getParams()['dbname'] ?? '';
            // Create the database using a connection where the dbname was not set.
            $this->noDbNameConn->createSchemaManager()->createDatabase($shlinkDatabase);

            return [];
        }
    }
}
