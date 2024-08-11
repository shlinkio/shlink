<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Util;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Util\IpAddressUtils;

class IpAddressUtilsTest extends TestCase
{
    #[Test]
    #[TestWith(['', false], 'empty')]
    #[TestWith(['invalid', false], 'invalid')]
    #[TestWith(['1.2.3.4', true], 'static IP')]
    #[TestWith(['456.2.385.4', false], 'invalid IP')]
    #[TestWith(['192.168.1.0/24', true], 'CIDR block')]
    #[TestWith(['1.2.*.*', true], 'wildcard pattern')]
    #[TestWith(['1.2.*.1', true], 'in-between wildcard pattern')]
    public function isStaticIpCidrOrWildcardReturnsExpectedResult(string $candidate, bool $expected): void
    {
        self::assertEquals($expected, IpAddressUtils::isStaticIpCidrOrWildcard($candidate));
    }
}
