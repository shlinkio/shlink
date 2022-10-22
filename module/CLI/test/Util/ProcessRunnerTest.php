<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Util;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\CLI\Util\ProcessRunner;
use Symfony\Component\Console\Helper\DebugFormatterHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class ProcessRunnerTest extends TestCase
{
    private ProcessRunner $runner;
    private MockObject $helper;
    private MockObject $formatter;
    private MockObject $process;
    private MockObject $output;

    protected function setUp(): void
    {
        $this->helper = $this->createMock(ProcessHelper::class);
        $this->formatter = $this->createMock(DebugFormatterHelper::class);
        $helperSet = $this->createMock(HelperSet::class);
        $helperSet->method('get')->with($this->equalTo('debug_formatter'))->willReturn($this->formatter);
        $this->helper->method('getHelperSet')->with()->willReturn($helperSet);
        $this->process = $this->createMock(Process::class);
        $this->output = $this->createMock(OutputInterface::class);

        $this->runner = new ProcessRunner($this->helper, fn () => $this->process);
    }

    /** @test */
    public function noMessagesAreWrittenWhenOutputIsNotVerbose(): void
    {
        $this->output->expects($this->exactly(2))->method('isVeryVerbose')->with()->willReturn(false);
        $this->output->expects($this->once())->method('isDebug')->with()->willReturn(false);
        $this->output->expects($this->never())->method('write');
        $this->process->expects($this->once())->method('mustRun')->withAnyParameters()->willReturnSelf();
        $this->process->expects($this->never())->method('isSuccessful');
        $this->process->expects($this->never())->method('getCommandLine');
        $this->helper->expects($this->never())->method('wrapCallback');
        $this->formatter->expects($this->never())->method('start');
        $this->formatter->expects($this->never())->method('stop');

        $this->runner->run($this->output, []);
    }

    /** @test */
    public function someMessagesAreWrittenWhenOutputIsVerbose(): void
    {
        $this->output->expects($this->exactly(2))->method('isVeryVerbose')->with()->willReturn(true);
        $this->output->expects($this->once())->method('isDebug')->with()->willReturn(false);
        $this->output->expects($this->exactly(2))->method('write')->withAnyParameters();
        $this->process->expects($this->once())->method('mustRun')->withAnyParameters()->willReturnSelf();
        $this->process->expects($this->exactly(2))->method('isSuccessful')->with()->willReturn(true);
        $this->process->expects($this->once())->method('getCommandLine')->with()->willReturn('true');
        $this->formatter->expects($this->once())->method('start')->withAnyParameters()->willReturn('');
        $this->formatter->expects($this->once())->method('stop')->withAnyParameters()->willReturn('');
        $this->helper->expects($this->never())->method('wrapCallback');

        $this->runner->run($this->output, []);
    }

    /** @test */
    public function wrapsCallbackWhenOutputIsDebug(): void
    {
        $this->output->expects($this->exactly(2))->method('isVeryVerbose')->with()->willReturn(false);
        $this->output->expects($this->once())->method('isDebug')->with()->willReturn(true);
        $this->output->expects($this->never())->method('write');
        $this->process->expects($this->once())->method('mustRun')->withAnyParameters()->willReturnSelf();
        $this->process->expects($this->never())->method('isSuccessful');
        $this->process->expects($this->never())->method('getCommandLine');
        $this->helper->expects($this->once())->method('wrapCallback')->withAnyParameters()->willReturn(
            function (): void {
            },
        );
        $this->formatter->expects($this->never())->method('start');
        $this->formatter->expects($this->never())->method('stop');

        $this->runner->run($this->output, []);
    }
}
