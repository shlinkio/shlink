<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\ShortUrl;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\CLI\Command\ShortUrl\DeleteExpiredShortUrlsCommand;
use Shlinkio\Shlink\CLI\Util\ExitCode;
use Shlinkio\Shlink\Core\ShortUrl\DeleteShortUrlServiceInterface;
use Shlinkio\Shlink\Core\ShortUrl\Model\ExpiredShortUrlsConditions;
use ShlinkioTest\Shlink\CLI\Util\CliTestUtils;
use Symfony\Component\Console\Tester\CommandTester;

class DeleteExpiredShortUrlsCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private MockObject & DeleteShortUrlServiceInterface $service;

    protected function setUp(): void
    {
        $this->service = $this->createMock(DeleteShortUrlServiceInterface::class);
        $this->commandTester = CliTestUtils::testerForCommand(new DeleteExpiredShortUrlsCommand($this->service));
    }

    #[Test]
    public function warningIsDisplayedAndExecutionCanBeCancelled(): void
    {
        $this->service->expects($this->never())->method('countExpiredShortUrls');
        $this->service->expects($this->never())->method('deleteExpiredShortUrls');

        $this->commandTester->setInputs(['n']);
        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();
        $status = $this->commandTester->getStatusCode();

        self::assertStringContainsString('Careful!', $output);
        self::assertEquals(ExitCode::EXIT_WARNING, $status);
    }

    #[Test]
    #[TestWith([[], [], true])]
    #[TestWith([['--force' => true], [], false])]
    #[TestWith([['-f' => true], [], false])]
    #[TestWith([[], ['interactive' => false], false])]
    public function deletionIsExecutedByDefault(array $input, array $options, bool $expectsWarning): void
    {
        $this->service->expects($this->never())->method('countExpiredShortUrls');
        $this->service->expects($this->once())->method('deleteExpiredShortUrls')->willReturn(5);

        $this->commandTester->setInputs(['y']);
        $this->commandTester->execute($input, $options);
        $output = $this->commandTester->getDisplay();
        $status = $this->commandTester->getStatusCode();

        if ($expectsWarning) {
            self::assertStringContainsString('Careful!', $output);
        } else {
            self::assertStringNotContainsString('Careful!', $output);
        }
        self::assertStringContainsString('5 expired short URLs have been deleted', $output);
        self::assertEquals(ExitCode::EXIT_SUCCESS, $status);
    }

    #[Test]
    public function countIsExecutedDuringDryRun(): void
    {
        $this->service->expects($this->once())->method('countExpiredShortUrls')->willReturn(38);
        $this->service->expects($this->never())->method('deleteExpiredShortUrls');

        $this->commandTester->execute(['--dry-run' => true]);
        $output = $this->commandTester->getDisplay();
        $status = $this->commandTester->getStatusCode();

        self::assertStringNotContainsString('Careful!', $output);
        self::assertStringContainsString('There are 38 expired short URLs matching provided conditions', $output);
        self::assertEquals(ExitCode::EXIT_SUCCESS, $status);
    }

    #[Test]
    #[TestWith([[], new ExpiredShortUrlsConditions()])]
    #[TestWith([['--evaluate-max-visits' => true], new ExpiredShortUrlsConditions(maxVisitsReached: true)])]
    public function providesExpectedConditionsToService(array $extraInput, ExpiredShortUrlsConditions $conditions): void
    {
        $this->service->expects($this->once())->method('countExpiredShortUrls')->with($conditions)->willReturn(4);
        $this->commandTester->execute(['--dry-run' => true, ...$extraInput]);
    }
}
