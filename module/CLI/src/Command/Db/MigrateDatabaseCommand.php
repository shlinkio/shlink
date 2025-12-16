<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Db;

use Shlinkio\Shlink\CLI\Util\ProcessRunnerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Lock\LockFactory;

class MigrateDatabaseCommand extends AbstractDatabaseCommand
{
    public const string NAME = 'db:migrate';
    public const string SCRIPT = 'vendor/doctrine/migrations/bin/doctrine-migrations.php';
    public const string COMMAND = 'migrations:migrate';

    public function __construct(
        LockFactory $locker,
        private readonly ProcessRunnerInterface $processRunner,
    ) {
        parent::__construct($locker);
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setHidden()
            ->setDescription('Runs database migrations, which will ensure the shlink database is up to date.');
    }

    protected function lockedExecute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->writeln('<fg=blue>Migrating database...</>');
        $this->processRunner->run($output, [self::SCRIPT, self::COMMAND, '--no-interaction']);
        $io->success('Database properly migrated!');

        return self::SUCCESS;
    }
}
