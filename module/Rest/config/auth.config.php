<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest;

use Laminas\ServiceManager\AbstractFactory\ConfigAbstractFactory;

return [

    'auth' => [
        'routes_whitelist' => [
            Action\HealthAction::class,
            Action\ShortUrl\SingleStepCreateShortUrlAction::class,
            ConfigProvider::UNVERSIONED_HEALTH_ENDPOINT_NAME,
        ],
    ],

    'dependencies' => [
        'factories' => [
            Middleware\AuthenticationMiddleware::class => ConfigAbstractFactory::class,
        ],
    ],

    ConfigAbstractFactory::class => [
        Middleware\AuthenticationMiddleware::class => [Service\ApiKeyService::class, 'config.auth.routes_whitelist'],
    ],

];
