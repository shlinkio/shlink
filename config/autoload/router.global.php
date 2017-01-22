<?php
use Zend\Expressive\Router\FastRouteRouter;

return [

    'router' => [
        'fastroute' => [
            FastRouteRouter::CONFIG_CACHE_ENABLED => true,
            FastRouteRouter::CONFIG_CACHE_FILE => 'data/cache/fastroute_cached_routes.php',
        ],
    ],

];
