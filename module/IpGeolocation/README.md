#  IP Address Geolocation module

Shlink module with tools to locate an IP address suing different strategies.

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
