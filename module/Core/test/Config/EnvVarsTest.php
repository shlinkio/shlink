<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Config;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Config\EnvVars;

use function putenv;

class EnvVarsTest extends TestCase
{
    protected function setUp(): void
    {
        putenv(EnvVars::BASE_PATH->value . '=the_base_path');
        putenv(EnvVars::DB_NAME->value . '=shlink');

        $envFilePath = __DIR__ . '/../DB_PASSWORD.env';
        putenv(EnvVars::DB_PASSWORD->value . '_FILE=' . $envFilePath);
    }

    protected function tearDown(): void
    {
        putenv(EnvVars::BASE_PATH->value);
        putenv(EnvVars::DB_NAME->value);
        putenv(EnvVars::DB_PASSWORD->value . '_FILE');
    }

    #[Test, DataProvider('provideExistingEnvVars')]
    public function existsInEnvReturnsExpectedValue(EnvVars $envVar, bool $exists): void
    {
        self::assertEquals($exists, $envVar->existsInEnv());
    }

    public static function provideExistingEnvVars(): iterable
    {
        yield 'DB_NAME (is set)' => [EnvVars::DB_NAME, true];
        yield 'BASE_PATH (is set)' => [EnvVars::BASE_PATH, true];
        yield 'DB_DRIVER (has default)' => [EnvVars::DB_DRIVER, true];
        yield 'DEFAULT_REGULAR_404_REDIRECT' => [EnvVars::DEFAULT_REGULAR_404_REDIRECT, false];
    }

    #[Test, DataProvider('provideEnvVarsValues')]
    public function expectedValueIsLoadedFromEnv(EnvVars $envVar, mixed $expected): void
    {
        self::assertEquals($expected, $envVar->loadFromEnv());
    }

    public static function provideEnvVarsValues(): iterable
    {
        yield 'DB_NAME (is set)' => [EnvVars::DB_NAME, 'shlink'];
        yield 'BASE_PATH (is set)' => [EnvVars::BASE_PATH, 'the_base_path'];
        yield 'DB_DRIVER (has default)' => [EnvVars::DB_DRIVER, 'sqlite'];
    }

    #[Test]
    public function fallsBackToReadEnvVarsFromFile(): void
    {
        self::assertEquals('this_is_the_password', EnvVars::DB_PASSWORD->loadFromEnv());
    }

    #[Test]
    #[TestWith(['mysql', '3306'])]
    #[TestWith(['maria', '3306'])]
    #[TestWith(['postgres', '5432'])]
    #[TestWith(['mssql', '1433'])]
    public function defaultPortIsResolvedBasedOnDbDriver(string $dbDriver, string $expectedPort): void
    {
        putenv(EnvVars::DB_DRIVER->value . '=' . $dbDriver);

        try {
            self::assertEquals($expectedPort, EnvVars::DB_PORT->loadFromEnv());
        } finally {
            putenv(EnvVars::DB_DRIVER->value);
        }
    }
}
