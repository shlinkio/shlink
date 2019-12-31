<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest;

use Zend\ServiceManager\AbstractFactory\ConfigAbstractFactory;

return [

    'auth' => [
        'routes_whitelist' => [
            Action\HealthAction::class,
            Action\ShortUrl\SingleStepCreateShortUrlAction::class,
        ],

        'plugins' => [
            'factories' => [
                Authentication\Plugin\ApiKeyHeaderPlugin::class => ConfigAbstractFactory::class,
                Authentication\Plugin\AuthorizationHeaderPlugin::class => ConfigAbstractFactory::class,
            ],
            'aliases' => [
                Authentication\Plugin\ApiKeyHeaderPlugin::HEADER_NAME =>
                    Authentication\Plugin\ApiKeyHeaderPlugin::class,
                Authentication\Plugin\AuthorizationHeaderPlugin::HEADER_NAME =>
                    Authentication\Plugin\AuthorizationHeaderPlugin::class,
            ],
        ],
    ],

    'dependencies' => [
        'factories' => [
            Authentication\AuthenticationPluginManager::class =>
                Authentication\AuthenticationPluginManagerFactory::class,
            Authentication\RequestToHttpAuthPlugin::class => ConfigAbstractFactory::class,

            Middleware\AuthenticationMiddleware::class => ConfigAbstractFactory::class,
        ],
    ],

    ConfigAbstractFactory::class => [
        Authentication\Plugin\AuthorizationHeaderPlugin::class => [Authentication\JWTService::class],
        Authentication\Plugin\ApiKeyHeaderPlugin::class => [Service\ApiKeyService::class],

        Authentication\RequestToHttpAuthPlugin::class => [Authentication\AuthenticationPluginManager::class],

        Middleware\AuthenticationMiddleware::class => [
            Authentication\RequestToHttpAuthPlugin::class,
            'config.auth.routes_whitelist',
        ],
    ],

];
