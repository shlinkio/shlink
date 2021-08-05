<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Db;

use Shlinkio\Shlink\CLI\Command\Util\AbstractLockedCommand;
use Shlinkio\Shlink\CLI\Command\Util\LockedCommandConfig;
use Shlinkio\Shlink\CLI\Util\ProcessRunnerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Process\PhpExecutableFinder;

abstract class AbstractDatabaseCommand extends AbstractLockedCommand
{
    private string $phpBinary;

    public function __construct(
        LockFactory $locker,
        private ProcessRunnerInterface $processRunner,
        PhpExecutableFinder $phpFinder,
    ) {
        parent::__construct($locker);
        $this->phpBinary = $phpFinder->find(false) ?: 'php';
    }

    protected function runPhpCommand(OutputInterface $output, array $command): void
    {
        $command = [$this->phpBinary, ...$command, '--no-interaction'];
        $this->processRunner->run($output, $command);
    }

    protected function getLockConfig(): LockedCommandConfig
    {
        return LockedCommandConfig::blocking($this->getName() ?? static::class);
    }
}
