<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Util;

use Closure;
use Symfony\Component\Console\Helper\DebugFormatterHelper;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

use function spl_object_id;
use function sprintf;
use function str_replace;

class ProcessRunner implements ProcessRunnerInterface
{
    private const int TIMEOUT = 1_200; // 20 minutes

    /** @var Closure(string[] $cmd): Process */
    private Closure $createProcess;

    /**
     * @param null|(callable(string[] $cmd): Process) $createProcess
     */
    public function __construct(private readonly ProcessHelper $helper, callable|null $createProcess = null)
    {
        $this->createProcess = $createProcess !== null
            ? $createProcess(...)
            : static fn (array $cmd) => new Process($cmd, timeout: self::TIMEOUT);
    }

    /**
     * @inheritDoc
     */
    public function run(OutputInterface $output, array $cmd): void
    {
        if ($output instanceof ConsoleOutputInterface) {
            $output = $output->getErrorOutput();
        }

        /** @var DebugFormatterHelper $formatter */
        $formatter = $this->helper->getHelperSet()?->get('debug_formatter') ?? new DebugFormatterHelper();
        $process = ($this->createProcess)($cmd);

        if ($output->isVeryVerbose()) {
            $output->write(
                $formatter->start(
                    (string) spl_object_id($process),
                    str_replace('<', '\\<', $process->getCommandLine()),
                ),
            );
        }

        $callback = $output->isDebug() ? $this->helper->wrapCallback($output, $process) : null;
        $process->mustRun($callback);

        if ($output->isVeryVerbose()) {
            $message = $process->isSuccessful()
                ? 'Command ran successfully'
                : sprintf(
                    '%s Command did not run successfully',
                    $process->getExitCode(),
                );
            $output->write($formatter->stop((string) spl_object_id($process), $message, $process->isSuccessful()));
        }
    }
}
