<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Db;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\Db\MigrateDatabaseCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Lock\Factory as Locker;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Process\PhpExecutableFinder;

class MigrateDatabaseCommandTest extends TestCase
{
    /** @var CommandTester */
    private $commandTester;
    /** @var ObjectProphecy */
    private $processHelper;

    public function setUp(): void
    {
        $locker = $this->prophesize(Locker::class);
        $lock = $this->prophesize(LockInterface::class);
        $lock->acquire(Argument::any())->willReturn(true);
        $lock->release()->will(function () {
        });
        $locker->createLock(Argument::cetera())->willReturn($lock->reveal());

        $phpExecutableFinder = $this->prophesize(PhpExecutableFinder::class);
        $phpExecutableFinder->find(false)->willReturn('/usr/local/bin/php');

        $this->processHelper = $this->prophesize(ProcessHelper::class);

        $command = new MigrateDatabaseCommand(
            $locker->reveal(),
            $this->processHelper->reveal(),
            $phpExecutableFinder->reveal()
        );
        $app = new Application();
        $app->add($command);

        $this->commandTester = new CommandTester($command);
    }

    /**
     * @test
     * @dataProvider provideVerbosities
     */
    public function migrationsCommandIsRunWithProperVerbosity(int $verbosity): void
    {
        $runCommand = $this->processHelper->run(Argument::type(OutputInterface::class), [
            '/usr/local/bin/php',
            MigrateDatabaseCommand::DOCTRINE_HELPER_SCRIPT,
            MigrateDatabaseCommand::DOCTRINE_HELPER_COMMAND,
        ], null, null, $verbosity);

        $this->commandTester->execute([], [
            'verbosity' => $verbosity,
        ]);
        $output = $this->commandTester->getDisplay();

        if ($verbosity >= OutputInterface::VERBOSITY_VERBOSE) {
            $this->assertStringContainsString('Migrating database...', $output);
            $this->assertStringContainsString('Database properly migrated!', $output);
        }
        $runCommand->shouldHaveBeenCalledOnce();
    }

    public function provideVerbosities(): iterable
    {
        yield 'debug' => [OutputInterface::VERBOSITY_DEBUG];
        yield 'normal' => [OutputInterface::VERBOSITY_NORMAL];
        yield 'quiet' => [OutputInterface::VERBOSITY_QUIET];
        yield 'verbose' => [OutputInterface::VERBOSITY_VERBOSE];
        yield 'very verbose' => [OutputInterface::VERBOSITY_VERY_VERBOSE];
    }
}
