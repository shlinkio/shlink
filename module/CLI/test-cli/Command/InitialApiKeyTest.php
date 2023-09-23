<?php

declare(strict_types=1);

namespace ShlinkioCliTest\Shlink\CLI\Command;

use PHPUnit\Framework\Attributes\Test;
use Shlinkio\Shlink\CLI\Command\Api\InitialApiKeyCommand;
use Shlinkio\Shlink\TestUtils\CliTest\CliTestCase;

class InitialApiKeyTest extends CliTestCase
{
    #[Test]
    public function createsNoKeyWhenOtherApiKeysAlreadyExist(): void
    {
        [$output] = $this->exec([InitialApiKeyCommand::NAME, 'new_api_key', '-v']);

        self::assertEquals(
            <<<OUT
            Other API keys already exist. Initial API key creation skipped.

            OUT,
            $output,
        );
    }
}
