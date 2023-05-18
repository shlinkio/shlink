<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Visit;

use Shlinkio\Shlink\CLI\Util\ExitCode;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractDeleteVisitsCommand extends Command
{
    final protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);
        if (! $this->confirm($io)) {
            $io->info('Operation aborted');
            return ExitCode::EXIT_SUCCESS;
        }

        return $this->doExecute($input, $io);
    }

    private function confirm(SymfonyStyle $io): bool
    {
        $io->warning($this->getWarningMessage());
        return $io->confirm('<comment>Continue deleting visits?</comment>', false);
    }

    abstract protected function doExecute(InputInterface $input, SymfonyStyle $io): ?int;

    abstract protected function getWarningMessage(): string;
}
