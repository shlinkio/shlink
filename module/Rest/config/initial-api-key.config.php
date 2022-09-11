<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest;

use Mezzio\Application;
use Shlinkio\Shlink\Core\Config\EnvVars;

use const PHP_SAPI;

return [

    'initial_api_key' => PHP_SAPI !== 'cli' ? null : EnvVars::INITIAL_API_KEY->loadFromEnv(),

    'dependencies' => [
        'delegators' => [
            Application::class => [
                ApiKey\InitialApiKeyDelegator::class,
            ],
        ],
    ],

];
