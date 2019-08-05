<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Db;

use Doctrine\DBAL\Connection;
use Shlinkio\Shlink\CLI\Command\Util\LockedCommandConfig;
use Shlinkio\Shlink\CLI\Util\ExitCodes;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Lock\Factory as Locker;
use Symfony\Component\Process\PhpExecutableFinder;

use function Functional\contains;

class CreateDatabaseCommand extends AbstractDatabaseCommand
{
    public const NAME = 'db:create';
    private const DOCTRINE_HELPER_SCRIPT = 'vendor/doctrine/orm/bin/doctrine.php';
    private const DOCTRINE_HELPER_COMMAND = 'orm:schema-tool:create';

    /** @var Connection */
    private $conn;

    public function __construct(
        Locker $locker,
        ProcessHelper $processHelper,
        PhpExecutableFinder $phpFinder,
        Connection $conn
    ) {
        parent::__construct($locker, $processHelper, $phpFinder);
        $this->conn = $conn;
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription(
                'Creates the database needed for shlink to work. It will do nothing if the database already exists'
            );
    }

    protected function lockedExecute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->checkDbExists();

        if ($this->schemaExists()) {
            $io->success('Database already exists.');
            return ExitCodes::EXIT_SUCCESS;
        }

        // Create database
        $io->writeln('Creating database tables...');
        $this->runPhpCommand($output, [self::DOCTRINE_HELPER_SCRIPT, self::DOCTRINE_HELPER_COMMAND]);
        $io->success('Database properly created!');

        return ExitCodes::EXIT_SUCCESS;
    }

    private function checkDbExists(): void
    {
        $schemaManager = $this->conn->getSchemaManager();
        $databases = $schemaManager->listDatabases();
        $shlinkDatabase = $this->conn->getDatabase();

        if (! contains($databases, $shlinkDatabase)) {
            $schemaManager->createDatabase($shlinkDatabase);
        }
    }

    private function schemaExists(): bool
    {
        // If at least one of the shlink tables exist, we will consider the database exists somehow.
        // Any inconsistency will be taken care by the migrations
        $schemaManager = $this->conn->getSchemaManager();
        return ! empty($schemaManager->listTableNames());
    }

    protected function getLockConfig(): LockedCommandConfig
    {
        return new LockedCommandConfig($this->getName(), true);
    }
}
