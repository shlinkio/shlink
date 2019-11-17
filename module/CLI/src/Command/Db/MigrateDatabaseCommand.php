<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Db;

use Shlinkio\Shlink\CLI\Util\ExitCodes;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateDatabaseCommand extends AbstractDatabaseCommand
{
    public const NAME = 'db:migrate';
    public const DOCTRINE_MIGRATIONS_SCRIPT = 'vendor/doctrine/migrations/bin/doctrine-migrations.php';
    public const DOCTRINE_MIGRATE_COMMAND = 'migrations:migrate';

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Runs database migrations, which will ensure the shlink database is up to date.');
    }

    protected function lockedExecute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->writeln('<fg=blue>Migrating database...</>');
        $this->runPhpCommand($output, [self::DOCTRINE_MIGRATIONS_SCRIPT, self::DOCTRINE_MIGRATE_COMMAND]);
        $io->success('Database properly migrated!');

        return ExitCodes::EXIT_SUCCESS;
    }
}
