<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Util;

use IPLib\Address\IPv4;
use IPLib\Factory;
use IPLib\Range\RangeInterface;
use Shlinkio\Shlink\Core\Exception\InvalidIpFormatException;

use function array_keys;
use function array_map;
use function explode;
use function implode;
use function Shlinkio\Shlink\Core\ArrayUtils\some;
use function str_contains;

final class IpAddressUtils
{
    /**
     * Checks if an IP address matches any of provided groups.
     * Every group can be a static IP address (100.200.80.40), a CIDR block (192.168.10.0/24) or a wildcard pattern
     * (11.22.*.*).
     *
     * Matching will happen as follows:
     *  * Static IP address -> strict equality with provided IP address.
     *  * CIDR block -> provided IP address is part of that block.
     *  * Wildcard -> static parts match the corresponding ones in provided IP address.
     *
     * @param string[] $groups
     * @throws InvalidIpFormatException
     */
    public static function ipAddressMatchesGroups(string $ipAddress, array $groups): bool
    {
        $ip = IPv4::parseString($ipAddress);
        if ($ip === null) {
            throw InvalidIpFormatException::fromInvalidIp($ipAddress);
        }

        $ipAddressParts = explode('.', $ipAddress);

        return some($groups, function (string $value) use ($ip, $ipAddressParts): bool {
            $range = str_contains($value, '*')
                ? self::parseValueWithWildcards($value, $ipAddressParts)
                : Factory::parseRangeString($value);

            return $range !== null && $ip->matches($range);
        });
    }

    private static function parseValueWithWildcards(string $value, array $ipAddressParts): ?RangeInterface
    {
        $octets = explode('.', $value);
        $keys = array_keys($octets);

        // Replace wildcard parts with the corresponding ones from the remote address
        return Factory::parseRangeString(
            implode('.', array_map(
                fn (string $part, int $index) => $part === '*' ? $ipAddressParts[$index] : $part,
                $octets,
                $keys,
            )),
        );
    }
}
