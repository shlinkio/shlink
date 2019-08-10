# Shlink Common

This library provides some utils and conventions for web apps. It's main purpose is to be used on [Shlink](https://github.com/shlinkio/shlink) project, but any PHP project can take advantage.

Most of the elements it provides require a [PSR-11] container, and it's easy to integrate on [expressive] applications thanks to the `ConfigProvider` it includes.

## Cache

A [doctrine cache] adapter is registered, which returns different instances depending on your configuration:
 
 * An `ArrayCache` instance when the `debug` config is set to true.
 * A `PredisCache` instance when the `cache.redis` config is defined.
 * An `ArrayCache` instance when no `cache.redis` is defined and the APCu extension is not installed.
 * An `ApcuCache`instance when no `cache.redis` is defined and the APCu extension is installed.
 
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
