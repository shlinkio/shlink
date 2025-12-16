<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Db;

use Shlinkio\Shlink\CLI\Command\Util\CommandUtils;
use Shlinkio\Shlink\CLI\Command\Util\LockConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Lock\LockFactory;

abstract class AbstractDatabaseCommand extends Command
{
    public function __construct(private readonly LockFactory $locker)
    {
        parent::__construct();
    }

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return CommandUtils::executeWithLock(
            $this->locker,
            LockConfig::blocking($this->getName() ?? static::class),
            new SymfonyStyle($input, $output),
            fn () => $this->lockedExecute($input, $output),
        );
    }

    abstract protected function lockedExecute(InputInterface $input, OutputInterface $output): int;
}
