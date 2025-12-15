<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Util;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

class CommandUtils
{
    /**
     * Displays a warning and confirmation message before running a callback. If the response to the confirmation is
     * positive, the callback is executed normally.
     *
     * @param callable(): int $callback
     */
    public static function executeWithWarning(string $warning, SymfonyStyle $io, callable $callback): int
    {
        $io->warning($warning);
        if (! $io->confirm('<comment>Do you want to proceed?</comment>', default: false)) {
            $io->info('Operation aborted');
            return Command::SUCCESS;
        }

        return $callback();
    }
}
