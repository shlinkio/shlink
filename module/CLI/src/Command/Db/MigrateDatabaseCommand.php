<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Db;

use Shlinkio\Shlink\CLI\Command\Util\CommandUtils;
use Shlinkio\Shlink\CLI\Command\Util\LockConfig;
use Shlinkio\Shlink\CLI\Util\ProcessRunnerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Lock\LockFactory;

class MigrateDatabaseCommand extends Command
{
    public const string NAME = 'db:migrate';
    public const string SCRIPT = 'vendor/doctrine/migrations/bin/doctrine-migrations.php';
    public const string COMMAND = 'migrations:migrate';

    public function __construct(
        private readonly LockFactory $locker,
        private readonly ProcessRunnerInterface $processRunner,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setHidden()
            ->setDescription('Runs database migrations, which will ensure the shlink database is up to date.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        return CommandUtils::executeWithLock(
            $this->locker,
            LockConfig::blocking(self::NAME),
            $io,
            fn () => $this->executeCommand($io),
        );
    }

    private function executeCommand(SymfonyStyle $io): int
    {
        $io->writeln('<fg=blue>Migrating database...</>');
        $this->processRunner->run($io, [self::SCRIPT, self::COMMAND, '--no-interaction']);
        $io->success('Database properly migrated!');

        return self::SUCCESS;
    }
}
