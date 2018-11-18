<?php
declare(strict_types=1);

use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use RKA\Middleware\IpAddress;
use Shlinkio\Shlink\Core\Action;
use Shlinkio\Shlink\Core\Middleware;

return [

    'routes' => [
        [
            'name' => 'long-url-redirect',
            'path' => '/{shortCode}',
            'middleware' => [
                IpAddress::class,
                Action\RedirectAction::class,
            ],
            'allowed_methods' => [RequestMethod::METHOD_GET],
        ],
        [
            'name' => 'pixel-tracking',
            'path' => '/{shortCode}/track',
            'middleware' => [
                IpAddress::class,
                Action\PixelAction::class,
            ],
            'allowed_methods' => [RequestMethod::METHOD_GET],
        ],
        [
            'name' => 'short-url-qr-code',
            'path' => '/{shortCode}/qr-code[/{size:[0-9]+}]',
            'middleware' => [
                Middleware\QrCodeCacheMiddleware::class,
                Action\QrCodeAction::class,
            ],
            'allowed_methods' => [RequestMethod::METHOD_GET],
        ],
        [
            'name' => 'short-url-preview',
            'path' => '/{shortCode}/preview',
            'middleware' => Action\PreviewAction::class,
            'allowed_methods' => [RequestMethod::METHOD_GET],
        ],

        // Old QR code route. Deprecated
        [
            'name' => 'short-url-qr-code-old',
            'path' => '/qr/{shortCode}[/{size:[0-9]+}]',
            'middleware' => [
                Middleware\QrCodeCacheMiddleware::class,
                Action\QrCodeAction::class,
            ],
            'allowed_methods' => [RequestMethod::METHOD_GET],
        ],
    ],

];
