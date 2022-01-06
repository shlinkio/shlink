<?php

declare(strict_types=1);

use function Shlinkio\Shlink\Config\env;

return [

    'geolite2' => [
        'db_location' => __DIR__ . '/../../data/GeoLite2-City.mmdb',
        'temp_dir' => __DIR__ . '/../../data',
        'license_key' => env('GEOLITE_LICENSE_KEY'),
    ],

];
