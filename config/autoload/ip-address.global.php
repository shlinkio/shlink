<?php

declare(strict_types=1);

use RKA\Middleware\IpAddress;
use RKA\Middleware\Mezzio\IpAddressFactory;
use Shlinkio\Shlink\Core\Config\EnvVars;
use Shlinkio\Shlink\Core\Middleware\ReverseForwardedAddressesMiddlewareDecorator;

use function Shlinkio\Shlink\Core\splitByComma;

use const Shlinkio\Shlink\IP_ADDRESS_REQUEST_ATTRIBUTE;

return (static function (): array {
    $trustedProxies = EnvVars::TRUSTED_PROXIES->loadFromEnv();

    return [

        // Configuration for RKA\Middleware\IpAddress
        'rka' => [
            'ip_address' => [
                'attribute_name' => IP_ADDRESS_REQUEST_ATTRIBUTE,
                'check_proxy_headers' => true,
                'trusted_proxies' => splitByComma($trustedProxies),
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
            'delegators' => [
                // Make middleware decoration transparent to other parts of the code
                IpAddress::class => [
                    fn ($c, $n, callable $callback) =>
                        // If trusted proxies have been provided, use original middleware verbatim, otherwise decorate
                        // with workaround
                        $trustedProxies !== null
                            ? $callback()
                            : new ReverseForwardedAddressesMiddlewareDecorator($callback()),
                ],
            ],

        ],

    ];
})();
