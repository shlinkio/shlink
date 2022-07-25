<?php

declare(strict_types=1);

use Shlinkio\Shlink\Core\Config\EnvVars;

return [

    'rabbitmq' => [
        'enabled' => (bool) EnvVars::RABBITMQ_ENABLED->loadFromEnv(false),
        'host' => EnvVars::RABBITMQ_HOST->loadFromEnv(),
        'port' => (int) EnvVars::RABBITMQ_PORT->loadFromEnv('5672'),
        'user' => EnvVars::RABBITMQ_USER->loadFromEnv(),
        'password' => EnvVars::RABBITMQ_PASSWORD->loadFromEnv(),
        'vhost' => EnvVars::RABBITMQ_VHOST->loadFromEnv('/'),

        // Deprecated
        'legacy_visits_publishing' => (bool) EnvVars::RABBITMQ_LEGACY_VISITS_PUBLISHING->loadFromEnv(false),
    ],

];
