<?php

declare(strict_types=1);

use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use RKA\Middleware\IpAddress;
use Shlinkio\Shlink\Core\Action;
use Shlinkio\Shlink\Core\Middleware;

return [

    'routes' => [
        [
            'name' => Action\RedirectAction::class,
            'path' => '/{shortCode}',
            'middleware' => [
                IpAddress::class,
                Action\RedirectAction::class,
            ],
            'allowed_methods' => [RequestMethod::METHOD_GET],
        ],
        [
            'name' => Action\PixelAction::class,
            'path' => '/{shortCode}/track',
            'middleware' => [
                IpAddress::class,
                Action\PixelAction::class,
            ],
            'allowed_methods' => [RequestMethod::METHOD_GET],
        ],
        [
            'name' => Action\QrCodeAction::class,
            'path' => '/{shortCode}/qr-code[/{size:[0-9]+}]',
            'middleware' => [
                Middleware\QrCodeCacheMiddleware::class,
                Action\QrCodeAction::class,
            ],
            'allowed_methods' => [RequestMethod::METHOD_GET],
        ],

        // Deprecated routes
        [
            'name' => 'short-url-preview',
            'path' => '/{shortCode}/preview',
            'middleware' => Action\PreviewAction::class,
            'allowed_methods' => [RequestMethod::METHOD_GET],
        ],
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
