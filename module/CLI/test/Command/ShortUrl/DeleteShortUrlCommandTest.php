<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\ShortUrl;

use const PHP_EOL;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\ShortUrl\DeleteShortUrlCommand;
use Shlinkio\Shlink\Core\Exception;
use Shlinkio\Shlink\Core\Service\ShortUrl\DeleteShortUrlServiceInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use function array_pop;
use function sprintf;

class DeleteShortUrlCommandTest extends TestCase
{
    /** @var CommandTester */
    private $commandTester;
    /** @var ObjectProphecy */
    private $service;

    public function setUp(): void
    {
        $this->service = $this->prophesize(DeleteShortUrlServiceInterface::class);

        $command = new DeleteShortUrlCommand($this->service->reveal());
        $app = new Application();
        $app->add($command);

        $this->commandTester = new CommandTester($command);
    }

    /** @test */
    public function successMessageIsPrintedIfUrlIsProperlyDeleted(): void
    {
        $shortCode = 'abc123';
        $deleteByShortCode = $this->service->deleteByShortCode($shortCode, false)->will(function () {
        });

        $this->commandTester->execute(['shortCode' => $shortCode]);
        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString(
            sprintf('Short URL with short code "%s" successfully deleted.', $shortCode),
            $output
        );
        $deleteByShortCode->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function invalidShortCodePrintsMessage(): void
    {
        $shortCode = 'abc123';
        $deleteByShortCode = $this->service->deleteByShortCode($shortCode, false)->willThrow(
            Exception\InvalidShortCodeException::class
        );

        $this->commandTester->execute(['shortCode' => $shortCode]);
        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString(sprintf('Provided short code "%s" could not be found.', $shortCode), $output);
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
        $deleteByShortCode = $this->service->deleteByShortCode($shortCode, Argument::type('bool'))->will(
            function (array $args) {
                $ignoreThreshold = array_pop($args);

                if (!$ignoreThreshold) {
                    throw new Exception\DeleteShortUrlException(10);
                }
            }
        );
        $this->commandTester->setInputs($retryAnswer);

        $this->commandTester->execute(['shortCode' => $shortCode]);
        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString(sprintf(
            'It was not possible to delete the short URL with short code "%s" because it has more than 10 visits.',
            $shortCode
        ), $output);
        $this->assertStringContainsString($expectedMessage, $output);
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
        $deleteByShortCode = $this->service->deleteByShortCode($shortCode, false)->willThrow(
            new Exception\DeleteShortUrlException(10)
        );
        $this->commandTester->setInputs(['no']);

        $this->commandTester->execute(['shortCode' => $shortCode]);
        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString(sprintf(
            'It was not possible to delete the short URL with short code "%s" because it has more than 10 visits.',
            $shortCode
        ), $output);
        $this->assertStringContainsString('Short URL was not deleted.', $output);
        $deleteByShortCode->shouldHaveBeenCalledOnce();
    }
}
