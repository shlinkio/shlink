<?php
use Shlinkio\Shlink\Core\Action\RedirectAction;

return [

    'routes' => [
        [
            'name' => 'long-url-redirect',
            'path' => '/{shortCode}',
            'middleware' => RedirectAction::class,
            'allowed_methods' => ['GET'],
        ],
    ],

];
