<?php

declare(strict_types=1);

use Mezzio\Router\FastRouteRouter;

return [

    'router' => [
        'base_path' => '',

        'fastroute' => [
            FastRouteRouter::CONFIG_CACHE_ENABLED => true,
            FastRouteRouter::CONFIG_CACHE_FILE => 'data/cache/fastroute_cached_routes.php',
        ],
    ],

];
