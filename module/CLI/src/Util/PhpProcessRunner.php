<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Util;

use Override;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\PhpExecutableFinder;

/**
 * Wraps another process manager prefixing any command run with current PHP binary
 */
readonly class PhpProcessRunner implements ProcessRunnerInterface
{
    private string $phpBinary;

    public function __construct(private ProcessRunnerInterface $wrappedProcessRunner, PhpExecutableFinder $phpFinder)
    {
        $this->phpBinary = $phpFinder->find(includeArgs: false) ?: 'php';
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function run(OutputInterface $output, array $cmd): void
    {
        $this->wrappedProcessRunner->run($output, [$this->phpBinary, ...$cmd]);
    }
}
