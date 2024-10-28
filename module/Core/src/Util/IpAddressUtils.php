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
    public static function isStaticIpCidrOrWildcard(string $candidate): bool
    {
        return self::candidateToRange($candidate, ['0', '0', '0', '0']) !== null;
    }

    /**
     * Checks if an IP address matches any of provided groups.
     * Every group can be a static IP address (100.200.80.40), a CIDR block (192.168.10.0/24) or a wildcard pattern
     * (11.22.*.*).
     *
     * Matching will happen as follows:
     *  * Static IP address -> strict equality with provided IP address.
     *  * CIDR block -> provided IP address is part of that block.
     *  * Wildcard pattern -> static parts match the corresponding ones in provided IP address.
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

        return some($groups, function (string $group) use ($ip, $ipAddressParts): bool {
            $range = self::candidateToRange($group, $ipAddressParts);
            return $range !== null && $range->contains($ip);
        });
    }

    /**
     * Convert a static IP, CIDR block or wildcard pattern into a Range object
     *
     * @param string[] $ipAddressParts
     */
    private static function candidateToRange(string $candidate, array $ipAddressParts): RangeInterface|null
    {
        return str_contains($candidate, '*')
            ? self::parseValueWithWildcards($candidate, $ipAddressParts)
            : Factory::parseRangeString($candidate);
    }

    /**
     * Try to generate an IP range from a wildcard pattern.
     * Factory::parseRangeString can usually do this automatically, but only if wildcards are at the end. This also
     * covers cases where wildcards are in between.
     */
    private static function parseValueWithWildcards(string $value, array $ipAddressParts): RangeInterface|null
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
