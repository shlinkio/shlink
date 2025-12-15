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
use Symfony\Component\Process\PhpExecutableFinder;

abstract class AbstractDatabaseCommand extends Command
{
    private string $phpBinary;

    public function __construct(
        private readonly LockFactory $locker,
        private readonly ProcessRunnerInterface $processRunner,
        PhpExecutableFinder $phpFinder,
    ) {
        parent::__construct();
        $this->phpBinary = $phpFinder->find(false) ?: 'php';
    }

    protected function runPhpCommand(OutputInterface $output, array $command): void
    {
        $command = [$this->phpBinary, ...$command, '--no-interaction'];
        $this->processRunner->run($output, $command);
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
