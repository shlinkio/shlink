<?php

declare(strict_types=1);

use Shlinkio\Shlink\Core\Config\EnvVars;

return [

    'geolite2' => [
        'db_location' => __DIR__ . '/../../data/GeoLite2-City.mmdb',
        'temp_dir' => __DIR__ . '/../../data',
        'license_key' => EnvVars::GEOLITE_LICENSE_KEY()->loadFromEnv(),
    ],

];
