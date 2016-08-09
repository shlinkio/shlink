<?php
use Shlinkio\Shlink\Core\Action;

return [

    'routes' => [
        [
            'name' => 'long-url-redirect',
            'path' => '/{shortCode}',
            'middleware' => Action\RedirectAction::class,
            'allowed_methods' => ['GET'],
        ],
        [
            'name' => 'short-url-qr-code',
            'path' => '/qr/{shortCode}[/{size:[0-9]+}]',
            'middleware' => Action\QrCodeAction::class,
            'allowed_methods' => ['GET'],
        ],
    ],

];
