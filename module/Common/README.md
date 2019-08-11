# Shlink Common

This library provides some utils and conventions for web apps. It's main purpose is to be used on [Shlink](https://github.com/shlinkio/shlink) project, but any PHP project can take advantage.

Most of the elements it provides require a [PSR-11] container, and it's easy to integrate on [expressive] applications thanks to the `ConfigProvider` it includes.

## Install

Install this library using composer

    composer require shlinkio/shlink-common

> This library is also an expressive module which provides its own `ConfigProvider`. Add it to your configuration to get everything automatically set up.

## Cache

A [doctrine cache] adapter is registered, which returns different instances depending on your configuration:
 
 * An `ArrayCache` instance when the `debug` config is set to true or when the APUc extension is not installed and the `cache.redis` config is not defined.
 * An `ApcuCache`instance when no `cache.redis` is defined and the APCu extension is installed.
 * A `PredisCache` instance when the `cache.redis` config is defined.
 
 Any of the adapters will use the namespace defined in `cache.namespace` config entry.
 
 ```php
<?php
declare(strict_types=1);

return [

    'debug' => false,

    'cache' => [
        'namespace' => 'my_namespace',
        'redis' => [
            'servers' => [
                'tcp://1.1.1.1:6379',
                'tcp://2.2.2.2:6379',
                'tcp://3.3.3.3:6379',
            ],
        ],
    ],

];
```

When the `cache.redis` config is provided, a set of servers is expected. If only one server is provided, this library will treat it as a regular server, but if several servers are defined, it will treat them as a redis cluster and expect the servers to be configured as such.

## Middlewares

This module provides a set of useful middlewares, all registered as services in the container:

* **CloseDatabaseConnectionMiddleware**:

    Should be an early middleware in the pipeline. It makes use of the EntityManager that ensure the database connection is closed at the end of the request.

    It should be used when serving an app with a non-blocking IO server (like Swoole or ReactPHP), which persist services between requests.

* **LocaleMiddleware**:

    Sets the locale in the translator, based on the `Accapt-Language` header.

* **IpAddress** (from [akrabat/ip-address-middleware] package):

    Improves detection of the remote IP address.

    The set of headers which are inspected in order to search for the address can be customized using this configuration:

    ```php
    <?php
    declare(strict_types=1);

    return [

        'ip_address_resolution' => [
            'headers_to_inspect' => [
                'CF-Connecting-IP',
                'True-Client-IP',
                'X-Real-IP',
                'Forwarded',
                'X-Forwarded-For',
                'X-Forwarded',
                'X-Cluster-Client-Ip',
                'Client-Ip',
            ],
        ],

    ];
    ```
