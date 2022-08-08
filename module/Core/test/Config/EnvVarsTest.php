<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Config;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Config\EnvVars;

use function Functional\map;
use function putenv;

class EnvVarsTest extends TestCase
{
    protected function setUp(): void
    {
        putenv(EnvVars::BASE_PATH->value . '=the_base_path');
        putenv(EnvVars::DB_NAME->value . '=shlink');
    }

    protected function tearDown(): void
    {
        putenv(EnvVars::BASE_PATH->value . '=');
        putenv(EnvVars::DB_NAME->value . '=');
    }

    /**
     * @test
     * @dataProvider provideExistingEnvVars
     */
    public function existsInEnvReturnsExpectedValue(EnvVars $envVar, bool $exists): void
    {
        self::assertEquals($exists, $envVar->existsInEnv());
    }

    public function provideExistingEnvVars(): iterable
    {
        yield 'DB_NAME' => [EnvVars::DB_NAME, true];
        yield 'BASE_PATH' => [EnvVars::BASE_PATH, true];
        yield 'DB_DRIVER' => [EnvVars::DB_DRIVER, false];
        yield 'DEFAULT_REGULAR_404_REDIRECT' => [EnvVars::DEFAULT_REGULAR_404_REDIRECT, false];
    }

    /**
     * @test
     * @dataProvider provideEnvVarsValues
     */
    public function expectedValueIsLoadedFromEnv(EnvVars $envVar, mixed $expected, mixed $default): void
    {
        self::assertEquals($expected, $envVar->loadFromEnv($default));
    }

    public function provideEnvVarsValues(): iterable
    {
        yield 'DB_NAME without default' => [EnvVars::DB_NAME, 'shlink', null];
        yield 'DB_NAME with default' => [EnvVars::DB_NAME, 'shlink', 'foobar'];
        yield 'BASE_PATH without default' => [EnvVars::BASE_PATH, 'the_base_path', null];
        yield 'BASE_PATH with default' => [EnvVars::BASE_PATH, 'the_base_path', 'foobar'];
        yield 'DB_DRIVER without default' => [EnvVars::DB_DRIVER, null, null];
        yield 'DB_DRIVER with default' => [EnvVars::DB_DRIVER, 'foobar', 'foobar'];
    }

    /** @test */
    public function allValuesCanBeListed(): void
    {
        $expected = map(EnvVars::cases(), static fn (EnvVars $envVar) => $envVar->value);
        self::assertEquals(EnvVars::values(), $expected);
    }
}
