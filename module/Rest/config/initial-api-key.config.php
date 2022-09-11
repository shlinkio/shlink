<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest;

use Mezzio\Application;
use Shlinkio\Shlink\Core\Config\EnvVars;

use const PHP_SAPI;

return [

    // We will try to load the initial API key only for openswoole and RoadRunner.
    // For php-fpm, the check against the database would happen on every request, resulting in a very bad performance.
    'initial_api_key' => PHP_SAPI !== 'cli' ? null : EnvVars::INITIAL_API_KEY->loadFromEnv(),

    'dependencies' => [
        'delegators' => [
            Application::class => [
                ApiKey\InitialApiKeyDelegator::class,
            ],
        ],
    ],

];
