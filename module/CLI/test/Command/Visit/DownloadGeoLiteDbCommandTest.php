<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Visit;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\Visit\DownloadGeoLiteDbCommand;
use Shlinkio\Shlink\CLI\Exception\GeolocationDbUpdateFailedException;
use Shlinkio\Shlink\CLI\Util\ExitCodes;
use Shlinkio\Shlink\CLI\Util\GeolocationDbUpdaterInterface;
use ShlinkioTest\Shlink\CLI\CliTestUtilsTrait;
use Symfony\Component\Console\Tester\CommandTester;

use function sprintf;

class DownloadGeoLiteDbCommandTest extends TestCase
{
    use CliTestUtilsTrait;

    private CommandTester $commandTester;
    private ObjectProphecy $dbUpdater;

    protected function setUp(): void
    {
        $this->dbUpdater = $this->prophesize(GeolocationDbUpdaterInterface::class);
        $this->commandTester = $this->testerForCommand(new DownloadGeoLiteDbCommand($this->dbUpdater->reveal()));
    }

    /**
     * @test
     * @dataProvider provideFailureParams
     */
    public function showsProperMessageWhenGeoLiteUpdateFails(
        bool $olderDbExists,
        string $expectedMessage,
        int $expectedExitCode,
    ): void {
        $checkDbUpdate = $this->dbUpdater->checkDbUpdate(Argument::cetera())->will(
            function (array $args) use ($olderDbExists): void {
                [$beforeDownload, $handleProgress] = $args;

                $beforeDownload($olderDbExists);
                $handleProgress(100, 50);

                throw $olderDbExists
                    ? GeolocationDbUpdateFailedException::withOlderDb()
                    : GeolocationDbUpdateFailedException::withoutOlderDb();
            },
        );

        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();
        $exitCode = $this->commandTester->getStatusCode();

        self::assertStringContainsString(
            sprintf('%s GeoLite2 db file...', $olderDbExists ? 'Updating' : 'Downloading'),
            $output,
        );
        self::assertStringContainsString($expectedMessage, $output);
        self::assertSame($expectedExitCode, $exitCode);
        $checkDbUpdate->shouldHaveBeenCalledOnce();
    }

    public function provideFailureParams(): iterable
    {
        yield 'existing db' => [
            true,
            '[WARNING] GeoLite2 db file update failed. Visits will continue to be located',
            ExitCodes::EXIT_WARNING,
        ];
        yield 'not existing db' => [
            false,
            '[ERROR] GeoLite2 db file download failed. It will not be possible to locate',
            ExitCodes::EXIT_FAILURE,
        ];
    }

    /**
     * @test
     * @dataProvider provideSuccessParams
     */
    public function printsExpectedMessageWhenNoErrorOccurs(callable $checkUpdateBehavior, string $expectedMessage): void
    {
        $checkDbUpdate = $this->dbUpdater->checkDbUpdate(Argument::cetera())->will($checkUpdateBehavior);

        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();
        $exitCode = $this->commandTester->getStatusCode();

        self::assertStringContainsString($expectedMessage, $output);
        self::assertSame(ExitCodes::EXIT_SUCCESS, $exitCode);
        $checkDbUpdate->shouldHaveBeenCalledOnce();
    }

    public function provideSuccessParams(): iterable
    {
        yield 'up to date db' => [function (): void {
        }, '[INFO] GeoLite2 db file is up to date.'];
        yield 'outdated db' => [function (array $args): void {
            [$beforeDownload] = $args;
            $beforeDownload(true);
        }, '[OK] GeoLite2 db file properly downloaded.'];
    }
}
