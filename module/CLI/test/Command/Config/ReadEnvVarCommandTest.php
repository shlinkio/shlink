<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Config;

use Monolog\Test\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Shlinkio\Shlink\CLI\Command\Config\ReadEnvVarCommand;
use Shlinkio\Shlink\Core\Config\EnvVars;
use ShlinkioTest\Shlink\CLI\Util\CliTestUtils;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Tester\CommandTester;

class ReadEnvVarCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private string $envVarValue = 'the_env_var_value';

    protected function setUp(): void
    {
        $this->commandTester = CliTestUtils::testerForCommand(new ReadEnvVarCommand(fn () => $this->envVarValue));
    }

    #[Test]
    public function errorIsThrownIfProvidedEnvVarIsInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('foo is not a valid Shlink environment variable');

        $this->commandTester->execute(['env-var' => 'foo']);
    }

    #[Test]
    public function valueIsPrintedIfProvidedEnvVarIsValid(): void
    {
        $this->commandTester->execute(['env-var' => EnvVars::BASE_PATH->value]);
        $output = $this->commandTester->getDisplay();

        self::assertStringNotContainsString('Select the env var to read', $output);
        self::assertStringContainsString($this->envVarValue, $output);
    }

    #[Test]
    public function envVarNameIsRequestedIfArgumentIsMissing(): void
    {
        $this->commandTester->setInputs([EnvVars::BASE_PATH->value]);
        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('Select the env var to read', $output);
        self::assertStringContainsString($this->envVarValue, $output);
    }
}
