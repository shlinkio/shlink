<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Db;

use Shlinkio\Shlink\CLI\Command\Util\AbstractLockedCommand;
use Shlinkio\Shlink\CLI\Command\Util\LockedCommandConfig;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Process\PhpExecutableFinder;

abstract class AbstractDatabaseCommand extends AbstractLockedCommand
{
    private ProcessHelper $processHelper;
    private string $phpBinary;

    public function __construct(LockFactory $locker, ProcessHelper $processHelper, PhpExecutableFinder $phpFinder)
    {
        parent::__construct($locker);
        $this->processHelper = $processHelper;
        $this->phpBinary = $phpFinder->find(false) ?: 'php';
    }

    protected function runPhpCommand(OutputInterface $output, array $command): void
    {
        $command = [$this->phpBinary, ...$command, '--no-interaction'];
        $this->processHelper->mustRun($output, $command);
    }

    protected function getLockConfig(): LockedCommandConfig
    {
        return new LockedCommandConfig($this->getName(), true);
    }
}
