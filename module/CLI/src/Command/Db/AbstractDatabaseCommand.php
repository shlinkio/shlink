<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Db;

use Shlinkio\Shlink\CLI\Command\Util\AbstractLockedCommand;
use Shlinkio\Shlink\CLI\Command\Util\LockedCommandConfig;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\Factory as Locker;
use Symfony\Component\Process\PhpExecutableFinder;

use function array_unshift;

abstract class AbstractDatabaseCommand extends AbstractLockedCommand
{
    /** @var ProcessHelper */
    private $processHelper;
    /** @var string */
    private $phpBinary;

    public function __construct(Locker $locker, ProcessHelper $processHelper, PhpExecutableFinder $phpFinder)
    {
        parent::__construct($locker);
        $this->processHelper = $processHelper;
        $this->phpBinary = $phpFinder->find(false) ?: 'php';
    }

    protected function runPhpCommand(OutputInterface $output, array $command): void
    {
        array_unshift($command, $this->phpBinary);
        $this->processHelper->mustRun($output, $command);
    }

    protected function getLockConfig(): LockedCommandConfig
    {
        return new LockedCommandConfig($this->getName(), true);
    }
}
