<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Config;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Config\EnvVars;
use Shlinkio\Shlink\Core\Exception\InvalidArgumentException;

use function putenv;

class EnvVarsTest extends TestCase
{
    protected function setUp(): void
    {
        putenv(EnvVars::BASE_PATH . '=the_base_path');
        putenv(EnvVars::DB_NAME . '=shlink');
    }

    protected function tearDown(): void
    {
        putenv(EnvVars::BASE_PATH . '=');
        putenv(EnvVars::DB_NAME . '=');
    }

    /** @test */
    public function casesReturnsTheSameListEveryTime(): void
    {
        $list = EnvVars::cases();
        self::assertSame($list, EnvVars::cases());
        self::assertSame([
            EnvVars::DELETE_SHORT_URL_THRESHOLD,
            EnvVars::DB_DRIVER,
            EnvVars::DB_NAME,
            EnvVars::DB_USER,
            EnvVars::DB_PASSWORD,
            EnvVars::DB_HOST,
            EnvVars::DB_UNIX_SOCKET,
            EnvVars::DB_PORT,
            EnvVars::GEOLITE_LICENSE_KEY,
            EnvVars::REDIS_SERVERS,
            EnvVars::REDIS_SENTINEL_SERVICE,
            EnvVars::MERCURE_PUBLIC_HUB_URL,
            EnvVars::MERCURE_INTERNAL_HUB_URL,
            EnvVars::MERCURE_JWT_SECRET,
            EnvVars::DEFAULT_QR_CODE_SIZE,
            EnvVars::DEFAULT_QR_CODE_MARGIN,
            EnvVars::DEFAULT_QR_CODE_FORMAT,
            EnvVars::DEFAULT_QR_CODE_ERROR_CORRECTION,
            EnvVars::DEFAULT_QR_CODE_ROUND_BLOCK_SIZE,
            EnvVars::RABBITMQ_ENABLED,
            EnvVars::RABBITMQ_HOST,
            EnvVars::RABBITMQ_PORT,
            EnvVars::RABBITMQ_USER,
            EnvVars::RABBITMQ_PASSWORD,
            EnvVars::RABBITMQ_VHOST,
            EnvVars::DEFAULT_INVALID_SHORT_URL_REDIRECT,
            EnvVars::DEFAULT_REGULAR_404_REDIRECT,
            EnvVars::DEFAULT_BASE_URL_REDIRECT,
            EnvVars::REDIRECT_STATUS_CODE,
            EnvVars::REDIRECT_CACHE_LIFETIME,
            EnvVars::BASE_PATH,
            EnvVars::PORT,
            EnvVars::TASK_WORKER_NUM,
            EnvVars::WEB_WORKER_NUM,
            EnvVars::ANONYMIZE_REMOTE_ADDR,
            EnvVars::TRACK_ORPHAN_VISITS,
            EnvVars::DISABLE_TRACK_PARAM,
            EnvVars::DISABLE_TRACKING,
            EnvVars::DISABLE_IP_TRACKING,
            EnvVars::DISABLE_REFERRER_TRACKING,
            EnvVars::DISABLE_UA_TRACKING,
            EnvVars::DISABLE_TRACKING_FROM,
            EnvVars::DEFAULT_SHORT_CODES_LENGTH,
            EnvVars::IS_HTTPS_ENABLED,
            EnvVars::DEFAULT_DOMAIN,
            EnvVars::AUTO_RESOLVE_TITLES,
            EnvVars::REDIRECT_APPEND_EXTRA_PATH,
            EnvVars::VISITS_WEBHOOKS,
            EnvVars::NOTIFY_ORPHAN_VISITS_TO_WEBHOOKS,
        ], $list);
    }

    /**
     * @test
     * @dataProvider provideInvalidEnvVars
     */
    public function exceptionIsThrownWhenTryingToLoadInvalidEnvVar(string $envVar): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid env var: "' . $envVar . '"');

        EnvVars::{$envVar}();
    }

    public function provideInvalidEnvVars(): iterable
    {
        yield 'foo' => ['foo'];
        yield 'bar' => ['bar'];
        yield 'invalid' => ['invalid'];
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
        yield 'DB_NAME' => [EnvVars::DB_NAME(), true];
        yield 'BASE_PATH' => [EnvVars::BASE_PATH(), true];
        yield 'DB_DRIVER' => [EnvVars::DB_DRIVER(), false];
        yield 'DEFAULT_REGULAR_404_REDIRECT' => [EnvVars::DEFAULT_REGULAR_404_REDIRECT(), false];
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
        yield 'DB_NAME without default' => [EnvVars::DB_NAME(), 'shlink', null];
        yield 'DB_NAME with default' => [EnvVars::DB_NAME(), 'shlink', 'foobar'];
        yield 'BASE_PATH without default' => [EnvVars::BASE_PATH(), 'the_base_path', null];
        yield 'BASE_PATH with default' => [EnvVars::BASE_PATH(), 'the_base_path', 'foobar'];
        yield 'DB_DRIVER without default' => [EnvVars::DB_DRIVER(), null, null];
        yield 'DB_DRIVER with default' => [EnvVars::DB_DRIVER(), 'foobar', 'foobar'];
    }
}
