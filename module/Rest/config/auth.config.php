<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest;

use Zend\ServiceManager\AbstractFactory\ConfigAbstractFactory;

return [

    'auth' => [
        'routes_whitelist' => [
            Action\AuthenticateAction::class,
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

            Middleware\AuthenticationMiddleware::class => ConfigAbstractFactory::class,
        ],
    ],

    ConfigAbstractFactory::class => [
        Authentication\Plugin\AuthorizationHeaderPlugin::class => [Authentication\JWTService::class, 'translator'],

        Middleware\AuthenticationMiddleware::class => [
            Authentication\AuthenticationPluginManager::class,
            'translator',
            'config.auth.routes_whitelist',
            'Logger_Shlink',
        ],
    ],

];
