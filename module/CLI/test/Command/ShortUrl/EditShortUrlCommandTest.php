<?php

namespace ShlinkioTest\Shlink\CLI\Command\ShortUrl;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\CLI\Command\ShortUrl\EditShortUrlCommand;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifierInterface;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlServiceInterface;
use ShlinkioTest\Shlink\CLI\Util\CliTestUtils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

class EditShortUrlCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private MockObject & ShortUrlServiceInterface $shortUrlService;
    private MockObject & ShortUrlStringifierInterface $stringifier;

    protected function setUp(): void
    {
        $this->shortUrlService = $this->createMock(ShortUrlServiceInterface::class);
        $this->stringifier = $this->createMock(ShortUrlStringifierInterface::class);

        $command = new EditShortUrlCommand($this->shortUrlService, $this->stringifier);
        $this->commandTester = CliTestUtils::testerForCommand($command);
    }

    #[Test]
    public function successMessageIsPrintedIfNoErrorOccurs(): void
    {
        $this->shortUrlService->expects($this->once())->method('updateShortUrl')->willReturn(
            ShortUrl::createFake(),
        );
        $this->stringifier->expects($this->once())->method('stringify')->willReturn('https://s.test/foo');

        $this->commandTester->execute(['short-code' => 'foobar']);
        $output = $this->commandTester->getDisplay();
        $exitCode = $this->commandTester->getStatusCode();

        self::assertStringContainsString('Short URL "https://s.test/foo" properly edited', $output);
        self::assertEquals(Command::SUCCESS, $exitCode);
    }

    #[Test]
    #[TestWith([OutputInterface::VERBOSITY_NORMAL])]
    #[TestWith([OutputInterface::VERBOSITY_VERBOSE])]
    #[TestWith([OutputInterface::VERBOSITY_VERY_VERBOSE])]
    #[TestWith([OutputInterface::VERBOSITY_DEBUG])]
    public function errorIsPrintedInCaseOfFailure(int $verbosity): void
    {
        $e = ShortUrlNotFoundException::fromNotFound(ShortUrlIdentifier::fromShortCodeAndDomain('foo'));
        $this->shortUrlService->expects($this->once())->method('updateShortUrl')->willThrowException($e);
        $this->stringifier->expects($this->never())->method('stringify');

        $this->commandTester->execute(['short-code' => 'foo'], ['verbosity' => $verbosity]);
        $output = $this->commandTester->getDisplay();
        $exitCode = $this->commandTester->getStatusCode();

        self::assertStringContainsString('Short URL not found for "foo"', $output);
        if ($verbosity >= OutputInterface::VERBOSITY_VERBOSE) {
            self::assertStringContainsString('Exception trace:', $output);
        } else {
            self::assertStringNotContainsString('Exception trace:', $output);
        }
        self::assertEquals(Command::FAILURE, $exitCode);
    }
}
