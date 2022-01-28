<?php

declare(strict_types=1);

use Mezzio\Router\FastRouteRouter;
use Shlinkio\Shlink\Core\Config\EnvVars;

return [

    'router' => [
        'base_path' => EnvVars::BASE_PATH()->loadFromEnv(''),

        'fastroute' => [
            FastRouteRouter::CONFIG_CACHE_ENABLED => true,
            FastRouteRouter::CONFIG_CACHE_FILE => 'data/cache/fastroute_cached_routes.php',
        ],
    ],

];
