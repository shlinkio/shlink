<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest;

use Laminas\ServiceManager\AbstractFactory\ConfigAbstractFactory;

return [

    'auth' => [
        'routes_without_api_key' => [
            Action\HealthAction::class,
            // TODO Find a way to make this more transparent and not having to expose the prefix
            Action\AbstractRestAction::UNVERSIONED_NAME_PREFIX . Action\HealthAction::class,
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
            'config.auth.routes_without_api_key',
            'config.auth.routes_with_query_api_key',
        ],
    ],

];
