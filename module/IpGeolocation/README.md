# Shlink IP Address Geolocation module

Shlink module with tools to geolocate an IP address using different strategies.

Most of the elements it provides require a [PSR-11] container, and it's easy to integrate on [expressive] applications thanks to the `ConfigProvider` it includes.

## Install

Install this library using composer:

    composer require shlinkio/shlink-ip-geolocation

> This library is also an expressive module which provides its own `ConfigProvider`. Add it to your configuration to get everything automatically set up.

## *TODO*

```php
<?php
declare(strict_types=1);

return [

    'geolite2' => [
        'db_location' => __DIR__ . '/../../data/GeoLite2-City.mmdb',
        'temp_dir' => sys_get_temp_dir(),
        // 'download_from' => 'http://geolite.maxmind.com/download/geoip/database/GeoLite2-City.tar.gz',
    ],

];
```
