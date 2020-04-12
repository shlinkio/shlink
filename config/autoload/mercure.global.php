<?php

declare(strict_types=1);

use Laminas\ServiceManager\Proxy\LazyServiceFactory;
use Shlinkio\Shlink\Common\Mercure\LcobucciJwtProvider;

return [

    'mercure' => [
        'public_hub_url' => null,
        'internal_hub_url' => null,
        'jwt_secret' => null,
        'jwt_days_duration' => 5,
        'jwt_issuer' => 'Shlink',
    ],

    'dependencies' => [
        'delegators' => [
            LcobucciJwtProvider::class => [
                LazyServiceFactory::class,
            ],
        ],
        'lazy_services' => [
            'class_map' => [
                LcobucciJwtProvider::class => LcobucciJwtProvider::class,
            ],
        ],
    ],

];