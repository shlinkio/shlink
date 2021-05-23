<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\ShortUrl;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\ShortUrl\DeleteShortUrlCommand;
use Shlinkio\Shlink\Core\Exception;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Service\ShortUrl\DeleteShortUrlServiceInterface;
use ShlinkioTest\Shlink\CLI\CliTestUtilsTrait;
use Symfony\Component\Console\Tester\CommandTester;

use function array_pop;
use function sprintf;

use const PHP_EOL;

class DeleteShortUrlCommandTest extends TestCase
{
    use CliTestUtilsTrait;

    private CommandTester $commandTester;
    private ObjectProphecy $service;

    public function setUp(): void
    {
        $this->service = $this->prophesize(DeleteShortUrlServiceInterface::class);
        $this->commandTester = $this->testerForCommand(new DeleteShortUrlCommand($this->service->reveal()));
    }

    /** @test */
    public function successMessageIsPrintedIfUrlIsProperlyDeleted(): void
    {
        $shortCode = 'abc123';
        $deleteByShortCode = $this->service->deleteByShortCode(new ShortUrlIdentifier($shortCode), false)->will(
            function (): void {
            },
        );

        $this->commandTester->execute(['shortCode' => $shortCode]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString(
            sprintf('Short URL with short code "%s" successfully deleted.', $shortCode),
            $output,
        );
        $deleteByShortCode->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function invalidShortCodePrintsMessage(): void
    {
        $shortCode = 'abc123';
        $identifier = new ShortUrlIdentifier($shortCode);
        $deleteByShortCode = $this->service->deleteByShortCode($identifier, false)->willThrow(
            Exception\ShortUrlNotFoundException::fromNotFound($identifier),
        );

        $this->commandTester->execute(['shortCode' => $shortCode]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString(sprintf('No URL found with short code "%s"', $shortCode), $output);
        $deleteByShortCode->shouldHaveBeenCalledOnce();
    }

    /**
     * @test
     * @dataProvider provideRetryDeleteAnswers
     */
    public function deleteIsRetriedWhenThresholdIsReachedAndQuestionIsAccepted(
        array $retryAnswer,
        int $expectedDeleteCalls,
        string $expectedMessage
    ): void {
        $shortCode = 'abc123';
        $identifier = new ShortUrlIdentifier($shortCode);
        $deleteByShortCode = $this->service->deleteByShortCode($identifier, Argument::type('bool'))->will(
            function (array $args) use ($shortCode): void {
                $ignoreThreshold = array_pop($args);

                if (!$ignoreThreshold) {
                    throw Exception\DeleteShortUrlException::fromVisitsThreshold(10, $shortCode);
                }
            },
        );
        $this->commandTester->setInputs($retryAnswer);

        $this->commandTester->execute(['shortCode' => $shortCode]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString(sprintf(
            'Impossible to delete short URL with short code "%s" since it has more than "10" visits.',
            $shortCode,
        ), $output);
        self::assertStringContainsString($expectedMessage, $output);
        $deleteByShortCode->shouldHaveBeenCalledTimes($expectedDeleteCalls);
    }

    public function provideRetryDeleteAnswers(): iterable
    {
        yield 'answering yes to retry' => [['yes'], 2, 'Short URL with short code "abc123" successfully deleted.'];
        yield 'answering no to retry' => [['no'], 1, 'Short URL was not deleted.'];
        yield 'answering default to retry' => [[PHP_EOL], 1, 'Short URL was not deleted.'];
    }

    /** @test */
    public function deleteIsNotRetriedWhenThresholdIsReachedAndQuestionIsDeclined(): void
    {
        $shortCode = 'abc123';
        $deleteByShortCode = $this->service->deleteByShortCode(new ShortUrlIdentifier($shortCode), false)->willThrow(
            Exception\DeleteShortUrlException::fromVisitsThreshold(10, $shortCode),
        );
        $this->commandTester->setInputs(['no']);

        $this->commandTester->execute(['shortCode' => $shortCode]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString(sprintf(
            'Impossible to delete short URL with short code "%s" since it has more than "10" visits.',
            $shortCode,
        ), $output);
        self::assertStringContainsString('Short URL was not deleted.', $output);
        $deleteByShortCode->shouldHaveBeenCalledOnce();
    }
}
