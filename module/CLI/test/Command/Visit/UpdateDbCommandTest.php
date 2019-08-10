<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Visit;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\Visit\UpdateDbCommand;
use Shlinkio\Shlink\CLI\Util\ExitCodes;
use Shlinkio\Shlink\IpGeolocation\Exception\RuntimeException;
use Shlinkio\Shlink\IpGeolocation\GeoLite2\DbUpdaterInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class UpdateDbCommandTest extends TestCase
{
    /** @var CommandTester */
    private $commandTester;
    /** @var ObjectProphecy */
    private $dbUpdater;

    public function setUp(): void
    {
        $this->dbUpdater = $this->prophesize(DbUpdaterInterface::class);

        $command = new UpdateDbCommand($this->dbUpdater->reveal());
        $app = new Application();
        $app->add($command);

        $this->commandTester = new CommandTester($command);
    }

    /** @test */
    public function successMessageIsPrintedIfEverythingWorks(): void
    {
        $download = $this->dbUpdater->downloadFreshCopy(Argument::type('callable'))->will(function () {
        });

        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();
        $exitCode = $this->commandTester->getStatusCode();

        $this->assertStringContainsString('GeoLite2 database properly updated', $output);
        $this->assertEquals(ExitCodes::EXIT_SUCCESS, $exitCode);
        $download->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function errorMessageIsPrintedIfAnExceptionIsThrown(): void
    {
        $download = $this->dbUpdater->downloadFreshCopy(Argument::type('callable'))->willThrow(RuntimeException::class);

        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();
        $exitCode = $this->commandTester->getStatusCode();

        $this->assertStringContainsString('An error occurred while updating GeoLite2 database', $output);
        $this->assertEquals(ExitCodes::EXIT_FAILURE, $exitCode);
        $download->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function warningMessageIsPrintedIfAnExceptionIsThrownAndErrorsAreIgnored(): void
    {
        $download = $this->dbUpdater->downloadFreshCopy(Argument::type('callable'))->willThrow(RuntimeException::class);

        $this->commandTester->execute(['--ignoreErrors' => true]);
        $output = $this->commandTester->getDisplay();
        $exitCode = $this->commandTester->getStatusCode();

        $this->assertStringContainsString('ignored', $output);
        $this->assertEquals(ExitCodes::EXIT_SUCCESS, $exitCode);
        $download->shouldHaveBeenCalledOnce();
    }
}
