<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Util;

use Closure;
use Shlinkio\Shlink\CLI\Command\Util\LockedCommandConfig;
use Symfony\Component\Console\Helper\DebugFormatterHelper;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

use function spl_object_hash;
use function sprintf;
use function str_replace;

class ProcessRunner implements ProcessRunnerInterface
{
    private Closure $createProcess;

    public function __construct(private ProcessHelper $helper, ?callable $createProcess = null)
    {
        $this->createProcess = $createProcess !== null
            ? Closure::fromCallable($createProcess)
            : static fn (array $cmd) => new Process($cmd, null, null, null, LockedCommandConfig::DEFAULT_TTL);
    }

    public function run(OutputInterface $output, array $cmd): void
    {
        if ($output instanceof ConsoleOutputInterface) {
            $output = $output->getErrorOutput();
        }

        /** @var DebugFormatterHelper $formatter */
        $formatter = $this->helper->getHelperSet()?->get('debug_formatter') ?? new DebugFormatterHelper();
        /** @var Process $process */
        $process = ($this->createProcess)($cmd);

        if ($output->isVeryVerbose()) {
            $output->write(
                $formatter->start(spl_object_hash($process), str_replace('<', '\\<', $process->getCommandLine())),
            );
        }

        $callback = $output->isDebug() ? $this->helper->wrapCallback($output, $process) : null;
        $process->mustRun($callback);

        if ($output->isVeryVerbose()) {
            $message = $process->isSuccessful() ? 'Command ran successfully' : sprintf(
                '%s Command did not run successfully',
                $process->getExitCode(),
            );
            $output->write($formatter->stop(spl_object_hash($process), $message, $process->isSuccessful()));
        }
    }
}
