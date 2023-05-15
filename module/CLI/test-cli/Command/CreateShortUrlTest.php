<?php

declare(strict_types=1);

namespace ShlinkioCliTest\Shlink\CLI\Command;

use PHPUnit\Framework\Attributes\Test;
use Shlinkio\Shlink\CLI\Command\ShortUrl\CreateShortUrlCommand;
use Shlinkio\Shlink\CLI\Command\ShortUrl\ListShortUrlsCommand;
use Shlinkio\Shlink\CLI\Util\ExitCode;
use Shlinkio\Shlink\TestUtils\CliTest\CliTestCase;

class CreateShortUrlTest extends CliTestCase
{
    #[Test]
    public function defaultDomainIsIgnoredWhenExplicitlyProvided(): void
    {
        $slug = 'testing-default-domain';
        $defaultDomain = 's.test';

        [$output, $exitCode] = $this->exec(
            [CreateShortUrlCommand::NAME, 'https://example.com', '--domain', $defaultDomain, '--custom-slug', $slug],
        );

        self::assertEquals(ExitCode::EXIT_SUCCESS, $exitCode);
        self::assertStringContainsString('Generated short URL: http://' . $defaultDomain . '/' . $slug, $output);

        [$listOutput] = $this->exec([ListShortUrlsCommand::NAME, '--show-domain', '--search-term', $slug]);
        self::assertStringContainsString('DEFAULT', $listOutput);
    }
}
