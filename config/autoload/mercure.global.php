<?php

declare(strict_types=1);

use Laminas\ServiceManager\Proxy\LazyServiceFactory;
use Shlinkio\Shlink\Common\Mercure\LcobucciJwtProvider;
use Symfony\Component\Mercure\Hub;
use Symfony\Component\Mercure\HubInterface;

use function Shlinkio\Shlink\Common\env;

return (static function (): array {
    $publicUrl = env('MERCURE_PUBLIC_HUB_URL');

    return [

        'mercure' => [
            'public_hub_url' => $publicUrl,
            'internal_hub_url' => env('MERCURE_INTERNAL_HUB_URL', $publicUrl),
            'jwt_secret' => env('MERCURE_JWT_SECRET'),
            'jwt_issuer' => 'Shlink',
        ],

        'dependencies' => [
            'delegators' => [
                LcobucciJwtProvider::class => [
                    LazyServiceFactory::class,
                ],
                Hub::class => [
                    LazyServiceFactory::class,
                ],
            ],
            'lazy_services' => [
                'class_map' => [
                    LcobucciJwtProvider::class => LcobucciJwtProvider::class,
                    Hub::class => HubInterface::class,
                ],
            ],
        ],

    ];
})();
