<?php

declare(strict_types=1);

namespace ShlinkioCliTest\Shlink\CLI\Command;

use PHPUnit\Framework\Attributes\Test;
use Shlinkio\Shlink\CLI\Command\Api\CreateKeyCommand;
use Shlinkio\Shlink\CLI\Util\ExitCode;
use Shlinkio\Shlink\TestUtils\CliTest\CliTestCase;

class CreateApiKeyTest extends CliTestCase
{
    #[Test]
    public function outputIsCorrect(): void
    {
        [$output, $exitCode] = $this->exec([CreateKeyCommand::NAME]);

        self::assertStringContainsString('[OK] Generated API key', $output);
        self::assertEquals(ExitCode::EXIT_SUCCESS, $exitCode);
    }

    #[Test]
    public function allowsCustomKeyToBeProvided(): void
    {
        [$output, $exitCode] = $this->exec([CreateKeyCommand::NAME, 'custom_api_key']);

        self::assertStringContainsString('[OK] Generated API key: "custom_api_key"', $output);
        self::assertEquals(ExitCode::EXIT_SUCCESS, $exitCode);
    }
}
