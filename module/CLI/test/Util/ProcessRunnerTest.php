<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Util;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Util\ProcessRunner;
use Symfony\Component\Console\Helper\DebugFormatterHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class ProcessRunnerTest extends TestCase
{
    use ProphecyTrait;

    private ProcessRunner $runner;
    private ObjectProphecy $helper;
    private ObjectProphecy $formatter;
    private ObjectProphecy $process;
    private ObjectProphecy $output;

    protected function setUp(): void
    {
        $this->helper = $this->prophesize(ProcessHelper::class);
        $this->formatter = $this->prophesize(DebugFormatterHelper::class);
        $helperSet = $this->prophesize(HelperSet::class);
        $helperSet->get('debug_formatter')->willReturn($this->formatter->reveal());
        $this->helper->getHelperSet()->willReturn($helperSet->reveal());
        $this->process = $this->prophesize(Process::class);

        $this->runner = new ProcessRunner($this->helper->reveal(), fn () => $this->process->reveal());
        $this->output = $this->prophesize(OutputInterface::class);
    }

    /** @test */
    public function noMessagesAreWrittenWhenOutputIsNotVerbose(): void
    {
        $isVeryVerbose = $this->output->isVeryVerbose()->willReturn(false);
        $isDebug = $this->output->isDebug()->willReturn(false);
        $mustRun = $this->process->mustRun(Argument::cetera())->willReturn($this->process->reveal());

        $this->runner->run($this->output->reveal(), []);

        $isVeryVerbose->shouldHaveBeenCalledTimes(2);
        $isDebug->shouldHaveBeenCalledOnce();
        $mustRun->shouldHaveBeenCalledOnce();
        $this->process->isSuccessful()->shouldNotHaveBeenCalled();
        $this->process->getCommandLine()->shouldNotHaveBeenCalled();
        $this->output->write(Argument::cetera())->shouldNotHaveBeenCalled();
        $this->helper->wrapCallback(Argument::cetera())->shouldNotHaveBeenCalled();
        $this->formatter->start(Argument::cetera())->shouldNotHaveBeenCalled();
        $this->formatter->stop(Argument::cetera())->shouldNotHaveBeenCalled();
    }

    /** @test */
    public function someMessagesAreWrittenWhenOutputIsVerbose(): void
    {
        $isVeryVerbose = $this->output->isVeryVerbose()->willReturn(true);
        $isDebug = $this->output->isDebug()->willReturn(false);
        $mustRun = $this->process->mustRun(Argument::cetera())->willReturn($this->process->reveal());
        $isSuccessful = $this->process->isSuccessful()->willReturn(true);
        $getCommandLine = $this->process->getCommandLine()->willReturn('true');
        $start = $this->formatter->start(Argument::cetera())->willReturn('');
        $stop = $this->formatter->stop(Argument::cetera())->willReturn('');

        $this->runner->run($this->output->reveal(), []);

        $isVeryVerbose->shouldHaveBeenCalledTimes(2);
        $isDebug->shouldHaveBeenCalledOnce();
        $mustRun->shouldHaveBeenCalledOnce();
        $this->output->write(Argument::cetera())->shouldHaveBeenCalledTimes(2);
        $this->helper->wrapCallback(Argument::cetera())->shouldNotHaveBeenCalled();
        $isSuccessful->shouldHaveBeenCalledTimes(2);
        $getCommandLine->shouldHaveBeenCalledOnce();
        $start->shouldHaveBeenCalledOnce();
        $stop->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function wrapsCallbackWhenOutputIsDebug(): void
    {
        $isVeryVerbose = $this->output->isVeryVerbose()->willReturn(false);
        $isDebug = $this->output->isDebug()->willReturn(true);
        $mustRun = $this->process->mustRun(Argument::cetera())->willReturn($this->process->reveal());
        $wrapCallback = $this->helper->wrapCallback(Argument::cetera())->willReturn(function (): void {
        });

        $this->runner->run($this->output->reveal(), []);

        $isVeryVerbose->shouldHaveBeenCalledTimes(2);
        $isDebug->shouldHaveBeenCalledOnce();
        $mustRun->shouldHaveBeenCalledOnce();
        $wrapCallback->shouldHaveBeenCalledOnce();
        $this->process->isSuccessful()->shouldNotHaveBeenCalled();
        $this->process->getCommandLine()->shouldNotHaveBeenCalled();
        $this->output->write(Argument::cetera())->shouldNotHaveBeenCalled();
        $this->formatter->start(Argument::cetera())->shouldNotHaveBeenCalled();
        $this->formatter->stop(Argument::cetera())->shouldNotHaveBeenCalled();
    }
}
