<?php

declare(strict_types=1);

namespace ShlinkioCliTest\Shlink\CLI\Command;

use PHPUnit\Framework\Attributes\Test;
use Shlinkio\Shlink\CLI\Command\Api\GenerateKeyCommand;
use Shlinkio\Shlink\TestUtils\CliTest\CliTestCase;
use Symfony\Component\Console\Command\Command;

class GenerateApiKeyTest extends CliTestCase
{
    #[Test]
    public function outputIsCorrect(): void
    {
        [$output, $exitCode] = $this->exec([GenerateKeyCommand::NAME]);

        self::assertStringContainsString('[OK] Generated API key', $output);
        self::assertEquals(Command::SUCCESS, $exitCode);
    }
}
