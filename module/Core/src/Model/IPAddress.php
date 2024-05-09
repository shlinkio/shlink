<?php

namespace Shlinkio\Shlink\Core\Model;

use function explode;
use function ip2long;
use function str_contains;
use function trim;

final class IPAddress
{
    public static function requestComesFromIPOrRange(string $ipOrRange): bool
    {
        // According to the documentation of ServerRequestInterface::getServerParam, the data does not necessarily have
        // to come from $_SERVER, since we want to be sure to get the correct value, we retrieve it from $_SERVER
        // directly
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $currentIp = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $currentIp = $_SERVER['REMOTE_ADDR'];
        }

        $IPs = explode(',', $ipOrRange);
        foreach ($IPs as $IP) {
            if (self::ipIsRange($IP)) {
                if (self::ipIsInRange($currentIp, $IP)) {
                    return true;
                }
            } else {
                if ($IP === $currentIp) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function ipIsRange(string $ip): bool
    {
        return str_contains($ip, '/');
    }

    private static function ipIsInRange(string $ip, string $CIDRRange): bool
    {
        // Inspired from https://stackoverflow.com/a/594134/6086785
        list($subnet, $mask) = explode('/', $CIDRRange);
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = -1 << (32 - $mask);
        $subnetLong &= $maskLong;
        return ($ipLong & $maskLong) === $subnetLong;
    }
}
