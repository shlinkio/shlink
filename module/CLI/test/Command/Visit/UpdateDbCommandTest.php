<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Visit;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\Visit\UpdateDbCommand;
use Shlinkio\Shlink\Common\Exception\RuntimeException;
use Shlinkio\Shlink\Common\IpGeolocation\GeoLite2\DbUpdaterInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class UpdateDbCommandTest extends TestCase
{
    /** @var CommandTester */
    private $commandTester;
    /** @var ObjectProphecy */
    private $dbUpdater;

    public function setUp()
    {
        $this->dbUpdater = $this->prophesize(DbUpdaterInterface::class);

        $command = new UpdateDbCommand($this->dbUpdater->reveal());
        $app = new Application();
        $app->add($command);

        $this->commandTester = new CommandTester($command);
    }

    /**
     * @test
     */
    public function successMessageIsPrintedIfEverythingWorks()
    {
        $download = $this->dbUpdater->downloadFreshCopy(Argument::type('callable'))->will(function () {
        });

        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        $this->assertContains('GeoLite2 database properly updated', $output);
        $download->shouldHaveBeenCalledOnce();
    }

    /**
     * @test
     */
    public function errorMessageIsPrintedIfAnExceptionIsThrown()
    {
        $download = $this->dbUpdater->downloadFreshCopy(Argument::type('callable'))->willThrow(RuntimeException::class);

        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        $this->assertContains('An error occurred while updating GeoLite2 database', $output);
        $download->shouldHaveBeenCalledOnce();
    }
}
