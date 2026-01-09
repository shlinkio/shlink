<?php

declare(strict_types=1);

use RKA\Middleware\IpAddress;
use RKA\Middleware\Mezzio\IpAddressFactory;
use Shlinkio\Shlink\Core\Config\EnvVars;

use function Shlinkio\Shlink\Core\splitByComma;

use const Shlinkio\Shlink\IP_ADDRESS_REQUEST_ATTRIBUTE;

return (static function (): array {
    $trustedProxies = EnvVars::TRUSTED_PROXIES->loadFromEnv();
    $proxiesIsHopCount = is_numeric($trustedProxies);

    return [

        // Configuration for RKA\Middleware\IpAddress
        'rka' => [
            'ip_address' => [
                'attribute_name' => IP_ADDRESS_REQUEST_ATTRIBUTE,
                'check_proxy_headers' => true,
                // List of trusted proxies
                'trusted_proxies' => $proxiesIsHopCount ? [] : splitByComma($trustedProxies),
                // Amount of addresses to skip from the right, before finding the visitor IP address
                'hop_count' => $proxiesIsHopCount ? (int) $trustedProxies : 0,
                'headers_to_inspect' => [
                    'CF-Connecting-IP',
                    'X-Forwarded-For',
                    'X-Forwarded',
                    'Forwarded',
                    'True-Client-IP',
                    'X-Real-IP',
                    'X-Cluster-Client-Ip',
                    'Client-Ip',
                ],
            ],
        ],

        'dependencies' => [
            'factories' => [
                IpAddress::class => IpAddressFactory::class,
            ],
        ],

    ];
})();
