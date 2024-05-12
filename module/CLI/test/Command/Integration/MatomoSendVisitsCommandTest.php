<?php

namespace ShlinkioTest\Shlink\CLI\Command\Integration;

use Exception;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\CLI\Command\Integration\MatomoSendVisitsCommand;
use Shlinkio\Shlink\CLI\Util\ExitCode;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Matomo\MatomoOptions;
use Shlinkio\Shlink\Core\Matomo\MatomoVisitSenderInterface;
use Shlinkio\Shlink\Core\Matomo\Model\SendVisitsResult;
use ShlinkioTest\Shlink\CLI\Util\CliTestUtils;

class MatomoSendVisitsCommandTest extends TestCase
{
    private MockObject & MatomoVisitSenderInterface $visitSender;

    protected function setUp(): void
    {
        $this->visitSender = $this->createMock(MatomoVisitSenderInterface::class);
    }

    #[Test]
    public function warningDisplayedIfIntegrationIsNotEnabled(): void
    {
        [$output, $exitCode] = $this->executeCommand(matomoEnabled: false);

        self::assertStringContainsString('Matomo integration is not enabled in this Shlink instance', $output);
        self::assertEquals(ExitCode::EXIT_WARNING, $exitCode);
    }

    #[Test]
    #[TestWith([true], 'interactive')]
    #[TestWith([false], 'not interactive')]
    public function warningIsOnlyDisplayedInInteractiveMode(bool $interactive): void
    {
        $this->visitSender->method('sendVisitsInDateRange')->willReturn(new SendVisitsResult());

        [$output] = $this->executeCommand(['y'], ['interactive' => $interactive]);

        if ($interactive) {
            self::assertStringContainsString('You are about to send visits', $output);
        } else {
            self::assertStringNotContainsString('You are about to send visits', $output);
        }
    }

    #[Test]
    #[TestWith([true])]
    #[TestWith([false])]
    public function canCancelExecutionInInteractiveMode(bool $interactive): void
    {
        $this->visitSender->expects($this->exactly($interactive ? 0 : 1))->method('sendVisitsInDateRange')->willReturn(
            new SendVisitsResult(),
        );
        $this->executeCommand(['n'], ['interactive' => $interactive]);
    }

    #[Test]
    #[TestWith([new SendVisitsResult(), 'There was no visits matching provided date range'])]
    #[TestWith([new SendVisitsResult(successfulVisits: 10), '10 visits sent to Matomo.'])]
    #[TestWith([new SendVisitsResult(successfulVisits: 2), '2 visits sent to Matomo.'])]
    #[TestWith([new SendVisitsResult(failedVisits: 238), 'Failed to send 238 visits to Matomo.'])]
    #[TestWith([new SendVisitsResult(failedVisits: 18), 'Failed to send 18 visits to Matomo.'])]
    #[TestWith([new SendVisitsResult(successfulVisits: 2, failedVisits: 35), '2 visits sent to Matomo. 35 failed.'])]
    #[TestWith([new SendVisitsResult(successfulVisits: 81, failedVisits: 6), '81 visits sent to Matomo. 6 failed.'])]
    public function expectedResultIsDisplayed(SendVisitsResult $result, string $expectedResultMessage): void
    {
        $this->visitSender->expects($this->once())->method('sendVisitsInDateRange')->willReturn($result);
        [$output, $exitCode] = $this->executeCommand(['y']);

        self::assertStringContainsString($expectedResultMessage, $output);
        self::assertEquals(ExitCode::EXIT_SUCCESS, $exitCode);
    }

    #[Test]
    public function printsResultOfSendingVisits(): void
    {
        $this->visitSender->method('sendVisitsInDateRange')->willReturnCallback(
            function (DateRange $_, MatomoSendVisitsCommand $command): SendVisitsResult {
                // Call it a few times for an easier match of its result in the command putput
                $command->success(0);
                $command->success(1);
                $command->success(2);
                $command->error(3, new Exception('Error'));
                $command->success(4);
                $command->error(5, new Exception('Error'));

                return new SendVisitsResult();
            },
        );

        [$output] = $this->executeCommand(['y']);

        self::assertStringContainsString('...E.E', $output);
    }

    #[Test]
    #[TestWith([[], 'All time'])]
    #[TestWith([['--since' => '2023-05-01'], 'Since 2023-05-01 00:00:00'])]
    #[TestWith([['--until' => '2023-05-01'], 'Until 2023-05-01 00:00:00'])]
    #[TestWith([
        ['--since' => '2023-05-01', '--until' => '2024-02-02 23:59:59'],
        'Between 2023-05-01 00:00:00 and 2024-02-02 23:59:59',
    ])]
    public function providedDateAreParsed(array $args, string $expectedMessage): void
    {
        [$output] = $this->executeCommand(['n'], args: $args);
        self::assertStringContainsString('Resolved date range -> ' . $expectedMessage, $output);
    }

    /**
     * @return array{string, int, MatomoSendVisitsCommand}
     */
    private function executeCommand(
        array $input = [],
        array $options = [],
        array $args = [],
        bool $matomoEnabled = true,
    ): array {
        $command = new MatomoSendVisitsCommand(new MatomoOptions(enabled: $matomoEnabled), $this->visitSender);
        $commandTester = CliTestUtils::testerForCommand($command);
        $commandTester->setInputs($input);
        $commandTester->execute($args, $options);

        $output = $commandTester->getDisplay();
        $exitCode = $commandTester->getStatusCode();

        return [$output, $exitCode, $command];
    }
}
