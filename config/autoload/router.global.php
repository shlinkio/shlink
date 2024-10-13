<?php

declare(strict_types=1);

use Mezzio\Router\FastRouteRouter;
use Shlinkio\Shlink\Core\Config\EnvVars;

return [

    'router' => [
        'base_path' => EnvVars::BASE_PATH->loadFromEnv(),

        'fastroute' => [
            // Disabling config cache for cli, ensures it's never used for RoadRunner, and also that console
            // commands don't generate a cache file that's then used by php-fpm web executions
            FastRouteRouter::CONFIG_CACHE_ENABLED => PHP_SAPI !== 'cli',
            FastRouteRouter::CONFIG_CACHE_FILE => 'data/cache/fastroute_cached_routes.php',
        ],
    ],

];
