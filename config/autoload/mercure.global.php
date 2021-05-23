<?php

declare(strict_types=1);

use Laminas\ServiceManager\Proxy\LazyServiceFactory;
use Shlinkio\Shlink\Common\Mercure\LcobucciJwtProvider;
use Symfony\Component\Mercure\Hub;
use Symfony\Component\Mercure\HubInterface;

return [

    'mercure' => [
        'public_hub_url' => null,
        'internal_hub_url' => null,
        'jwt_secret' => null,
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
