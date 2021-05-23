<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Util;

use Shlinkio\Shlink\CLI\Util\ExitCodes;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\LockFactory;

use function sprintf;

abstract class AbstractLockedCommand extends Command
{
    public function __construct(private LockFactory $locker)
    {
        parent::__construct();
    }

    final protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $lockConfig = $this->getLockConfig();
        $lock = $this->locker->createLock($lockConfig->lockName(), $lockConfig->ttl(), $lockConfig->isBlocking());

        if (! $lock->acquire($lockConfig->isBlocking())) {
            $output->writeln(
                sprintf('<comment>Command "%s" is already in progress. Skipping.</comment>', $lockConfig->lockName()),
            );
            return ExitCodes::EXIT_WARNING;
        }

        try {
            return $this->lockedExecute($input, $output);
        } finally {
            $lock->release();
        }
    }

    abstract protected function lockedExecute(InputInterface $input, OutputInterface $output): int;

    abstract protected function getLockConfig(): LockedCommandConfig;
}
