<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\ShortUrl;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\ShortUrl\ResolveUrlCommand;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Service\ShortUrl\ShortUrlResolverInterface;
use ShlinkioTest\Shlink\CLI\CliTestUtilsTrait;
use Symfony\Component\Console\Tester\CommandTester;

use function sprintf;

use const PHP_EOL;

class ResolveUrlCommandTest extends TestCase
{
    use CliTestUtilsTrait;

    private CommandTester $commandTester;
    private ObjectProphecy $urlResolver;

    public function setUp(): void
    {
        $this->urlResolver = $this->prophesize(ShortUrlResolverInterface::class);
        $this->commandTester = $this->testerForCommand(new ResolveUrlCommand($this->urlResolver->reveal()));
    }

    /** @test */
    public function correctShortCodeResolvesUrl(): void
    {
        $shortCode = 'abc123';
        $expectedUrl = 'http://domain.com/foo/bar';
        $shortUrl = ShortUrl::withLongUrl($expectedUrl);
        $this->urlResolver->resolveShortUrl(new ShortUrlIdentifier($shortCode))->willReturn($shortUrl)
                                                                               ->shouldBeCalledOnce();

        $this->commandTester->execute(['shortCode' => $shortCode]);
        $output = $this->commandTester->getDisplay();
        self::assertEquals('Long URL: ' . $expectedUrl . PHP_EOL, $output);
    }

    /** @test */
    public function incorrectShortCodeOutputsErrorMessage(): void
    {
        $identifier = new ShortUrlIdentifier('abc123');
        $shortCode = $identifier->shortCode();

        $this->urlResolver->resolveShortUrl($identifier)
            ->willThrow(ShortUrlNotFoundException::fromNotFound($identifier))
            ->shouldBeCalledOnce();

        $this->commandTester->execute(['shortCode' => $shortCode]);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString(sprintf('No URL found with short code "%s"', $shortCode), $output);
    }
}
