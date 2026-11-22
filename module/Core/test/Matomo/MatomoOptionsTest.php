<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Matomo;

use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Config\EnvVars;
use Shlinkio\Shlink\Core\Matomo\MatomoOptions;

use function putenv;

class MatomoOptionsTest extends TestCase
{
    #[Override]
    protected function tearDown(): void
    {
        putenv(EnvVars::MATOMO_SITE_ID->value);
    }

    #[Test]
    public function optionalIntsAreCasted(): void
    {
        putenv(EnvVars::MATOMO_SITE_ID->value . '=123');
        self::assertEquals(123, MatomoOptions::fromEnv()->siteId);
    }

    #[Test]
    public function optionalIntsWithoutValueAreKeptAsNull(): void
    {
        self::assertNull(MatomoOptions::fromEnv()->siteId);
    }
}
