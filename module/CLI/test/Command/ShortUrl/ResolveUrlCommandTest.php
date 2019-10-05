<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\ShortUrl;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\ShortUrl\ResolveUrlCommand;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\EntityDoesNotExistException;
use Shlinkio\Shlink\Core\Exception\InvalidShortCodeException;
use Shlinkio\Shlink\Core\Service\UrlShortener;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

use const PHP_EOL;

class ResolveUrlCommandTest extends TestCase
{
    /** @var CommandTester */
    private $commandTester;
    /** @var ObjectProphecy */
    private $urlShortener;

    public function setUp(): void
    {
        $this->urlShortener = $this->prophesize(UrlShortener::class);
        $command = new ResolveUrlCommand($this->urlShortener->reveal());
        $app = new Application();
        $app->add($command);

        $this->commandTester = new CommandTester($command);
    }

    /** @test */
    public function correctShortCodeResolvesUrl(): void
    {
        $shortCode = 'abc123';
        $expectedUrl = 'http://domain.com/foo/bar';
        $shortUrl = new ShortUrl($expectedUrl);
        $this->urlShortener->shortCodeToUrl($shortCode, null)->willReturn($shortUrl)
                                                             ->shouldBeCalledOnce();

        $this->commandTester->execute(['shortCode' => $shortCode]);
        $output = $this->commandTester->getDisplay();
        $this->assertEquals('Long URL: ' . $expectedUrl . PHP_EOL, $output);
    }

    /** @test */
    public function incorrectShortCodeOutputsErrorMessage(): void
    {
        $shortCode = 'abc123';
        $this->urlShortener->shortCodeToUrl($shortCode, null)->willThrow(EntityDoesNotExistException::class)
                                                             ->shouldBeCalledOnce();

        $this->commandTester->execute(['shortCode' => $shortCode]);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Provided short code "' . $shortCode . '" could not be found.', $output);
    }

    /** @test */
    public function wrongShortCodeFormatOutputsErrorMessage(): void
    {
        $shortCode = 'abc123';
        $this->urlShortener->shortCodeToUrl($shortCode, null)->willThrow(new InvalidShortCodeException())
                                                             ->shouldBeCalledOnce();

        $this->commandTester->execute(['shortCode' => $shortCode]);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Provided short code "' . $shortCode . '" has an invalid format.', $output);
    }
}
