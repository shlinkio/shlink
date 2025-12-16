<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Util;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\CLI\Util\PhpProcessRunner;
use Shlinkio\Shlink\CLI\Util\ProcessRunnerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\PhpExecutableFinder;

class PhpProcessRunnerTest extends TestCase
{
    private MockObject & ProcessRunnerInterface $wrapped;
    private MockObject & PhpExecutableFinder $executableFinder;

    protected function setUp(): void
    {
        $this->wrapped = $this->createMock(ProcessRunnerInterface::class);
        $this->executableFinder = $this->createMock(PhpExecutableFinder::class);
    }

    #[Test]
    #[TestWith([false, 'php'])]
    #[TestWith(['/usr/local/bin/php', '/usr/local/bin/php'])]
    public function commandsArePrefixedWithPhp(string|false $resolvedExecutable, string $expectedExecutable): void
    {
        $output = $this->createStub(OutputInterface::class);
        $command = ['foo', 'bar', 'baz'];

        $this->wrapped->expects($this->once())->method('run')->with($output, [$expectedExecutable, ...$command]);
        $this->executableFinder->expects($this->once())->method('find')->with(false)->willReturn($resolvedExecutable);

        new PhpProcessRunner($this->wrapped, $this->executableFinder)->run($output, $command);
    }
}
