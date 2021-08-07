<?php

declare(strict_types=1);

use Mezzio\Router\FastRouteRouter;

use function Shlinkio\Shlink\Common\env;

return [

    'router' => [
        'base_path' => env('BASE_PATH', ''),

        'fastroute' => [
            FastRouteRouter::CONFIG_CACHE_ENABLED => true,
            FastRouteRouter::CONFIG_CACHE_FILE => 'data/cache/fastroute_cached_routes.php',
        ],
    ],

];
