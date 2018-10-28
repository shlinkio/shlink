<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\ShortUrl;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\ShortUrl\DeleteShortUrlCommand;
use Shlinkio\Shlink\Core\Exception;
use Shlinkio\Shlink\Core\Service\ShortUrl\DeleteShortUrlServiceInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Zend\I18n\Translator\Translator;
use function array_pop;
use function sprintf;

class DeleteShortCodeCommandTest extends TestCase
{
    /**
     * @var CommandTester
     */
    private $commandTester;
    /**
     * @var ObjectProphecy
     */
    private $service;

    public function setUp()
    {
        $this->service = $this->prophesize(DeleteShortUrlServiceInterface::class);

        $command = new DeleteShortUrlCommand($this->service->reveal(), Translator::factory([]));
        $app = new Application();
        $app->add($command);

        $this->commandTester = new CommandTester($command);
    }

    /**
     * @test
     */
    public function successMessageIsPrintedIfUrlIsProperlyDeleted()
    {
        $shortCode = 'abc123';
        $deleteByShortCode = $this->service->deleteByShortCode($shortCode, false)->will(function () {
        });

        $this->commandTester->execute(['shortCode' => $shortCode]);
        $output = $this->commandTester->getDisplay();

        $this->assertContains(sprintf('Short URL with short code "%s" successfully deleted.', $shortCode), $output);
        $deleteByShortCode->shouldHaveBeenCalledTimes(1);
    }

    /**
     * @test
     */
    public function invalidShortCodePrintsMessage()
    {
        $shortCode = 'abc123';
        $deleteByShortCode = $this->service->deleteByShortCode($shortCode, false)->willThrow(
            Exception\InvalidShortCodeException::class
        );

        $this->commandTester->execute(['shortCode' => $shortCode]);
        $output = $this->commandTester->getDisplay();

        $this->assertContains(sprintf('Provided short code "%s" could not be found.', $shortCode), $output);
        $deleteByShortCode->shouldHaveBeenCalledTimes(1);
    }

    /**
     * @test
     */
    public function deleteIsRetriedWhenThresholdIsReachedAndQuestionIsAccepted()
    {
        $shortCode = 'abc123';
        $deleteByShortCode = $this->service->deleteByShortCode($shortCode, Argument::type('bool'))->will(
            function (array $args) {
                $ignoreThreshold = array_pop($args);

                if (!$ignoreThreshold) {
                    throw new Exception\DeleteShortUrlException(10);
                }
            }
        );
        $this->commandTester->setInputs(['yes']);

        $this->commandTester->execute(['shortCode' => $shortCode]);
        $output = $this->commandTester->getDisplay();

        $this->assertContains(sprintf(
            'It was not possible to delete the short URL with short code "%s" because it has more than 10 visits.',
            $shortCode
        ), $output);
        $this->assertContains(sprintf('Short URL with short code "%s" successfully deleted.', $shortCode), $output);
        $deleteByShortCode->shouldHaveBeenCalledTimes(2);
    }

    /**
     * @test
     */
    public function deleteIsNotRetriedWhenThresholdIsReachedAndQuestionIsDeclined()
    {
        $shortCode = 'abc123';
        $deleteByShortCode = $this->service->deleteByShortCode($shortCode, false)->willThrow(
            new Exception\DeleteShortUrlException(10)
        );
        $this->commandTester->setInputs(['no']);

        $this->commandTester->execute(['shortCode' => $shortCode]);
        $output = $this->commandTester->getDisplay();

        $this->assertContains(sprintf(
            'It was not possible to delete the short URL with short code "%s" because it has more than 10 visits.',
            $shortCode
        ), $output);
        $this->assertContains('Short URL was not deleted.', $output);
        $deleteByShortCode->shouldHaveBeenCalledTimes(1);
    }
}
