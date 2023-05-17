<?php

declare(strict_types=1);

namespace ShlinkioCliTest\Shlink\CLI\Command;

use Cake\Chronos\Chronos;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shlinkio\Shlink\CLI\Command\Api\ListKeysCommand;
use Shlinkio\Shlink\CLI\Util\ExitCode;
use Shlinkio\Shlink\TestUtils\CliTest\CliTestCase;

class ListApiKeysTest extends CliTestCase
{
    #[Test, DataProvider('provideFlags')]
    public function generatesExpectedOutput(array $flags, string $expectedOutput): void
    {
        [$output, $exitCode] = $this->exec([ListKeysCommand::NAME, ...$flags]);

        self::assertEquals($expectedOutput, $output);
        self::assertEquals(ExitCode::EXIT_SUCCESS, $exitCode);
    }

    public static function provideFlags(): iterable
    {
        $expiredApiKeyDate = Chronos::now()->subDay()->startOfDay()->toAtomString();
        $enabledOnlyOutput = <<<OUT
        +-----------------+------+---------------------------+--------------------------+
        | Key             | Name | Expiration date           | Roles                    |
        +-----------------+------+---------------------------+--------------------------+
        | valid_api_key   | -    | -                         | Admin                    |
        +-----------------+------+---------------------------+--------------------------+
        | expired_api_key | -    | {$expiredApiKeyDate} | Admin                    |
        +-----------------+------+---------------------------+--------------------------+
        | author_api_key  | -    | -                         | Author only              |
        +-----------------+------+---------------------------+--------------------------+
        | domain_api_key  | -    | -                         | Domain only: example.com |
        +-----------------+------+---------------------------+--------------------------+

        OUT;

        yield 'no flags' => [[], <<<OUT
            +------------------+------+------------+---------------------------+--------------------------+
            | Key              | Name | Is enabled | Expiration date           | Roles                    |
            +------------------+------+------------+---------------------------+--------------------------+
            | valid_api_key    | -    | +++        | -                         | Admin                    |
            +------------------+------+------------+---------------------------+--------------------------+
            | disabled_api_key | -    | ---        | -                         | Admin                    |
            +------------------+------+------------+---------------------------+--------------------------+
            | expired_api_key  | -    | ---        | {$expiredApiKeyDate} | Admin                    |
            +------------------+------+------------+---------------------------+--------------------------+
            | author_api_key   | -    | +++        | -                         | Author only              |
            +------------------+------+------------+---------------------------+--------------------------+
            | domain_api_key   | -    | +++        | -                         | Domain only: example.com |
            +------------------+------+------------+---------------------------+--------------------------+

            OUT];
        yield '-e' => [['-e'], $enabledOnlyOutput];
        yield '--enabled-only' => [['--enabled-only'], $enabledOnlyOutput];
    }
}
