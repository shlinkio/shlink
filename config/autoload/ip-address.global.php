<?php

declare(strict_types=1);

use Laminas\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use RKA\Middleware\IpAddress;
use RKA\Middleware\Mezzio\IpAddressFactory;
use Shlinkio\Shlink\Core\Middleware\ReverseForwardedAddressesMiddlewareDecorator;

use const Shlinkio\Shlink\IP_ADDRESS_REQUEST_ATTRIBUTE;

return [

    // Configuration for RKA\Middleware\IpAddress
    'rka' => [
        'ip_address' => [
            'attribute_name' => IP_ADDRESS_REQUEST_ATTRIBUTE,
            'check_proxy_headers' => true,
            'trusted_proxies' => [],
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
//            IpAddress::class => IpAddressFactory::class,
            'actual_ip_address_middleware' => IpAddressFactory::class,
            ReverseForwardedAddressesMiddlewareDecorator::class => ConfigAbstractFactory::class,
        ],
        'aliases' => [
            // Make sure the decorated middleware is resolved when getting IpAddress::class, to make this decoration
            // transparent for other parts of the code
            IpAddress::class => ReverseForwardedAddressesMiddlewareDecorator::class,
        ],
    ],

    ConfigAbstractFactory::class => [
        ReverseForwardedAddressesMiddlewareDecorator::class => ['actual_ip_address_middleware'],
    ],

];
