<?php

declare(strict_types=1);

namespace ShlinkioCliTest\Shlink\CLI\Command;

use PHPUnit\Framework\Attributes\Test;
use Shlinkio\Shlink\CLI\Command\RedirectRule\ManageRedirectRulesCommand;
use Shlinkio\Shlink\TestUtils\CliTest\CliTestCase;

class ManageRedirectRulesTest extends CliTestCase
{
    #[Test]
    public function printsErrorsWhenPassingInvalidValues(): void
    {
        [$output] = $this->exec([ManageRedirectRulesCommand::NAME, 'abc123'], [
            '0', // Add new rule
            'not-a-number', // Invalid priority
            '1', // Valid priority, to continue execution
            'invalid-long-url', // Invalid long URL
            'https://example.com', // Valid long URL, to continue execution
            '1', // Language condition type
            '', // Invalid required language
            'es-ES', // Valid language, to continue execution
            'no', // Do not add more conditions
            '4', // Discard changes
        ]);

        self::assertStringContainsString('The priority must be a numeric positive value', $output);
        self::assertStringContainsString('The input is not valid', $output);
        self::assertStringContainsString('The value is mandatory', $output);
    }
}
