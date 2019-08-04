<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Db;

use Shlinkio\Shlink\CLI\Command\Util\AbstractLockedCommand;
use Shlinkio\Shlink\CLI\Command\Util\LockedCommandConfig;
use Shlinkio\Shlink\CLI\Util\ExitCodes;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Lock\Factory as Locker;
use Symfony\Component\Process\PhpExecutableFinder;

class CreateDatabaseCommand extends AbstractLockedCommand
{
    public const NAME = 'db:create';
    private const DOCTRINE_HELPER_SCRIPT = 'vendor/doctrine/orm/bin/doctrine.php';
    private const DOCTRINE_HELPER_COMMAND = 'orm:schema-tool:create';

    /** @var ProcessHelper */
    private $processHelper;
    /** @var string */
    private $phpBinary;

    public function __construct(Locker $locker, ProcessHelper $processHelper, PhpExecutableFinder $phpFinder)
    {
        parent::__construct($locker);
        $this->processHelper = $processHelper;
        $this->phpBinary = $phpFinder->find(false) ?: 'php';
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

        if ($this->dbExistsAndIsPopulated()) {
            $io->success('Database already exists.');
            return ExitCodes::EXIT_SUCCESS;
        }

        if (! $this->schemaExists()) {
            // TODO Create empty database
        }

        // Create database
        $io->writeln('Creating database tables...');
        $command = [$this->phpBinary, self::DOCTRINE_HELPER_SCRIPT, self::DOCTRINE_HELPER_COMMAND];
        $this->processHelper->run($output, $command);
        $io->success('Database properly created!');

        return ExitCodes::EXIT_SUCCESS;
    }

    private function dbExistsAndIsPopulated(): bool
    {
        // TODO Implement
        return false;
    }

    private function schemaExists(): bool
    {
        // TODO Implement
        return true;
    }

    protected function getLockConfig(): LockedCommandConfig
    {
        return new LockedCommandConfig($this->getName(), true);
    }
}
