<?php

declare(strict_types=1);

use Shlinkio\Shlink\Core\Config\EnvVars;
use Shlinkio\Shlink\Geolocation\Resolver\GeolocationStrategy;

use function Shlinkio\Shlink\Core\splitByComma;

return (static function (): array {
    $geoliteLicense = EnvVars::GEOLITE_LICENSE_KEY->loadFromEnv();
    $geolocationResolvers = array_map(
        GeolocationStrategy::from(...),
        // @mago-expect analysis:mixed-argument
        splitByComma(EnvVars::GEOLOCATION_STRATEGIES->loadFromEnv()),
    );

    // If a GeoLite2 license is set, and no geolocation strategies have been explicitly provided, fall back to using
    // GeoLite2 strategy only.
    // This ensures backwards compatibility with Shlink instances before GEOLOCATION_STRATEGIES was introduced.
    if ($geoliteLicense !== null && empty($geolocationResolvers)) {
        $geolocationResolvers = [GeolocationStrategy::GEOLITE2];
    }

    return [
        'geolocation_resolvers' => $geolocationResolvers,

        'geolite2' => [
            'db_location' => __DIR__ . '/../../data/GeoLite2-City.mmdb',
            'temp_dir' => __DIR__ . '/../../data/temp-geolite',
            'license_key' => $geoliteLicense,
        ],
    ];
})();
