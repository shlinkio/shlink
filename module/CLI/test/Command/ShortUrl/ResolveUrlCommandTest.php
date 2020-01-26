<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\ShortUrl;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\ShortUrl\ResolveUrlCommand;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Service\ShortUrl\ShortUrlResolverInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

use function sprintf;

use const PHP_EOL;

class ResolveUrlCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private ObjectProphecy $urlResolver;

    public function setUp(): void
    {
        $this->urlResolver = $this->prophesize(ShortUrlResolverInterface::class);
        $command = new ResolveUrlCommand($this->urlResolver->reveal());
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
        $this->urlResolver->shortCodeToShortUrl($shortCode, null)->willReturn($shortUrl)
                                                                 ->shouldBeCalledOnce();

        $this->commandTester->execute(['shortCode' => $shortCode]);
        $output = $this->commandTester->getDisplay();
        $this->assertEquals('Long URL: ' . $expectedUrl . PHP_EOL, $output);
    }

    /** @test */
    public function incorrectShortCodeOutputsErrorMessage(): void
    {
        $shortCode = 'abc123';
        $this->urlResolver->shortCodeToShortUrl($shortCode, null)
            ->willThrow(ShortUrlNotFoundException::fromNotFoundShortCode($shortCode))
            ->shouldBeCalledOnce();

        $this->commandTester->execute(['shortCode' => $shortCode]);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString(sprintf('No URL found with short code "%s"', $shortCode), $output);
    }
}
