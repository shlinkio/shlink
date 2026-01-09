<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Util;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Lock\LockFactory;

use function sprintf;

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

    /**
     * Runs a callback with a lock, making sure the lock is released after running the callback, and the callback does
     * not run if the lock is already acquired.
     *
     * @param callable(): int $callback
     */
    public static function executeWithLock(
        LockFactory $locker,
        LockConfig $lockConfig,
        SymfonyStyle $io,
        callable $callback,
    ): int {
        $lock = $locker->createLock($lockConfig->lockName, $lockConfig->ttl, $lockConfig->isBlocking);
        if (! $lock->acquire($lockConfig->isBlocking)) {
            $io->writeln(
                sprintf('<comment>Command "%s" is already in progress. Skipping.</comment>', $lockConfig->lockName),
            );
            return Command::INVALID;
        }

        try {
            return $callback();
        } finally {
            $lock->release();
        }
    }
}
