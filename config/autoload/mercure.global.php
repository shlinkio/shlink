<?php

declare(strict_types=1);

use Laminas\ServiceManager\Proxy\LazyServiceFactory;
use Shlinkio\Shlink\Common\Mercure\LcobucciJwtProvider;
use Shlinkio\Shlink\Core\Config\EnvVars;
use Symfony\Component\Mercure\Hub;
use Symfony\Component\Mercure\HubInterface;

return [

    // This config is used by shlink-common. Do not delete
    'mercure' => [
        'enabled' => EnvVars::MERCURE_ENABLED->loadFromEnv(),
        'public_hub_url' => EnvVars::MERCURE_PUBLIC_HUB_URL->loadFromEnv(),
        'internal_hub_url' => EnvVars::MERCURE_INTERNAL_HUB_URL->loadFromEnv(),
        'jwt_secret' => EnvVars::MERCURE_JWT_SECRET->loadFromEnv(),
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
