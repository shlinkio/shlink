<?php

declare(strict_types=1);

use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use RKA\Middleware\IpAddress;
use Shlinkio\Shlink\Core\Action;

return [

    'routes' => [
        [
            'name' => Action\RobotsAction::class,
            'path' => '/robots.txt',
            'middleware' => [
                Action\RobotsAction::class,
            ],
            'allowed_methods' => [RequestMethod::METHOD_GET],
        ],
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
            'path' => '/{shortCode}/qr-code',
            'middleware' => [
                Action\QrCodeAction::class,
            ],
            'allowed_methods' => [RequestMethod::METHOD_GET],
        ],
    ],

];
