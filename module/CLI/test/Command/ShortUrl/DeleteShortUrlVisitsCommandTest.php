<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\ShortUrl;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\CLI\Command\ShortUrl\DeleteShortUrlVisitsCommand;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Model\BulkDeleteResult;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlVisitsDeleterInterface;
use ShlinkioTest\Shlink\CLI\Util\CliTestUtils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class DeleteShortUrlVisitsCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private MockObject & ShortUrlVisitsDeleterInterface $deleter;

    protected function setUp(): void
    {
        $this->deleter = $this->createMock(ShortUrlVisitsDeleterInterface::class);
        $this->commandTester = CliTestUtils::testerForCommand(new DeleteShortUrlVisitsCommand($this->deleter));
    }

    /**
     * @param list<string> $input
     */
    #[Test, DataProvider('provideCancellingInputs')]
    public function executionIsAbortedIfManuallyCancelled(array $input): void
    {
        $this->deleter->expects($this->never())->method('deleteShortUrlVisits');
        $this->commandTester->setInputs($input);

        $exitCode = $this->commandTester->execute(['short-code' => 'foo']);
        $output = $this->commandTester->getDisplay();

        self::assertEquals(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Operation aborted', $output);
    }

    public static function provideCancellingInputs(): iterable
    {
        yield 'default input' => [[]];
        yield 'no' => [['no']];
        yield 'n' => [['n']];
    }

    #[Test, DataProvider('provideErrorArgs')]
    public function warningIsPrintedInCaseOfNotFoundShortUrl(array $args, string $expectedError): void
    {
        $this->deleter->expects($this->once())->method('deleteShortUrlVisits')->willThrowException(
            new ShortUrlNotFoundException(),
        );
        $this->commandTester->setInputs(['yes']);

        $exitCode = $this->commandTester->execute($args);
        $output = $this->commandTester->getDisplay();

        self::assertEquals(Command::INVALID, $exitCode);
        self::assertStringContainsString($expectedError, $output);
    }

    public static function provideErrorArgs(): iterable
    {
        yield 'domain' => [['short-code' => 'foo'], 'Short URL not found for "foo"'];
        yield 'no domain' => [['short-code' => 'foo', '--domain' => 's.test'], 'Short URL not found for "s.test/foo"'];
    }

    #[Test]
    public function successMessageIsPrintedForValidShortUrls(): void
    {
        $this->deleter->expects($this->once())->method('deleteShortUrlVisits')->willReturn(new BulkDeleteResult(5));
        $this->commandTester->setInputs(['yes']);

        $exitCode = $this->commandTester->execute(['short-code' => 'foo']);
        $output = $this->commandTester->getDisplay();

        self::assertEquals(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Successfully deleted 5 visits', $output);
    }
}
