<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\ShortUrl;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\CLI\Command\ShortUrl\ResolveUrlCommand;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlResolverInterface;
use ShlinkioTest\Shlink\CLI\Util\CliTestUtils;
use Symfony\Component\Console\Tester\CommandTester;

use function sprintf;

use const PHP_EOL;

class ResolveUrlCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private MockObject & ShortUrlResolverInterface $urlResolver;

    protected function setUp(): void
    {
        $this->urlResolver = $this->createMock(ShortUrlResolverInterface::class);
        $this->commandTester = CliTestUtils::testerForCommand(new ResolveUrlCommand($this->urlResolver));
    }

    #[Test]
    public function correctShortCodeResolvesUrl(): void
    {
        $shortCode = 'abc123';
        $expectedUrl = 'http://domain.com/foo/bar';
        $shortUrl = ShortUrl::withLongUrl($expectedUrl);
        $this->urlResolver->expects($this->once())->method('resolveShortUrl')->with(
            ShortUrlIdentifier::fromShortCodeAndDomain($shortCode),
        )->willReturn($shortUrl);

        $this->commandTester->execute(['shortCode' => $shortCode]);
        $output = $this->commandTester->getDisplay();
        self::assertEquals('Long URL: ' . $expectedUrl . PHP_EOL, $output);
    }

    #[Test]
    public function incorrectShortCodeOutputsErrorMessage(): void
    {
        $identifier = ShortUrlIdentifier::fromShortCodeAndDomain('abc123');
        $shortCode = $identifier->shortCode;

        $this->urlResolver->expects($this->once())->method('resolveShortUrl')->with($identifier)->willThrowException(
            ShortUrlNotFoundException::fromNotFound($identifier),
        );

        $this->commandTester->execute(['shortCode' => $shortCode]);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString(sprintf('No URL found with short code "%s"', $shortCode), $output);
    }
}
