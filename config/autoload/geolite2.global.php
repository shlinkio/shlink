<?php

declare(strict_types=1);

return [

    'geolite2' => [
        'db_location' => __DIR__ . '/../../data/GeoLite2-City.mmdb',
        'temp_dir' => __DIR__ . '/../../data',
        'license_key' => 'G4Lm0C60yJsnkdPi', // Deprecated. Remove hardcoded license on v3
    ],

];
