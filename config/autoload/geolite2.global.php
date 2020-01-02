<?php

declare(strict_types=1);

return [

    'geolite2' => [
        'db_location' => __DIR__ . '/../../data/GeoLite2-City.mmdb',
        'temp_dir' => sys_get_temp_dir(),
        'download_from' =>
            'https://download.maxmind.com/app/geoip_download'
            . '?edition_id=GeoLite2-City&license_key=G4Lm0C60yJsnkdPi&suffix=tar.gz',
    ],

];
