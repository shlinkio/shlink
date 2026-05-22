<?php

declare(strict_types=1);

namespace ShlinkioCliTest\Shlink\CLI\Command;

use PHPUnit\Framework\Attributes\Test;
use Shlinkio\Shlink\CLI\Command\ShortUrl\EditShortUrlCommand;
use Shlinkio\Shlink\CLI\Command\ShortUrl\ResolveUrlCommand;
use Shlinkio\Shlink\TestUtils\CliTest\CliTestCase;
use Symfony\Component\Console\Command\Command;

class EditShortUrlTest extends CliTestCase
{
    #[Test]
    public function longUrlCanBeEdited(): void
    {
        [$originalOutput] = $this->exec([ResolveUrlCommand::NAME, 'abc123']);
        self::assertStringContainsString('Long URL: https://shlink.io', $originalOutput);

        [, $exitCode] = $this->exec([EditShortUrlCommand::NAME, 'abc123', '--long-url', 'https://example.com']);
        self::assertEquals(Command::SUCCESS, $exitCode);

        [$newOutput] = $this->exec([ResolveUrlCommand::NAME, 'abc123']);
        self::assertStringContainsString('Long URL: https://example.com', $newOutput);
    }
}
