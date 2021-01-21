<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest;

use Laminas\ServiceManager\AbstractFactory\ConfigAbstractFactory;

return [

    'auth' => [
        'routes_whitelist' => [
            Action\HealthAction::class,
            ConfigProvider::UNVERSIONED_HEALTH_ENDPOINT_NAME,
        ],

        'routes_with_query_api_key' => [
            Action\ShortUrl\SingleStepCreateShortUrlAction::class,
        ],
    ],

    'dependencies' => [
        'factories' => [
            Middleware\AuthenticationMiddleware::class => ConfigAbstractFactory::class,
        ],
    ],

    ConfigAbstractFactory::class => [
        Middleware\AuthenticationMiddleware::class => [
            Service\ApiKeyService::class,
            'config.auth.routes_whitelist',
            'config.auth.routes_with_query_api_key',
        ],
    ],

];
