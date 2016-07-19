<?php
use Shlinkio\Shlink\Core\Action\RedirectMiddleware;

return [

    'routes' => [
        [
            'name' => 'long-url-redirect',
            'path' => '/{shortCode}',
            'middleware' => RedirectMiddleware::class,
            'allowed_methods' => ['GET'],
        ],
    ],

];
