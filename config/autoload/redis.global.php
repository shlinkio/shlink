<?php

declare(strict_types=1);

use Shlinkio\Shlink\Core\Config\EnvVars;

return (static function (): array {
    $redisServers = EnvVars::REDIS_SERVERS->loadFromEnv();
    $pubSub = [
        'redis' => [
            'pub_sub_enabled' => $redisServers !== null && EnvVars::REDIS_PUB_SUB_ENABLED->loadFromEnv(false),
        ],
    ];

    return match ($redisServers) {
        null => $pubSub,
        default => [
            'cache' => [
                'redis' => [
                    'servers' => $redisServers,
                    'sentinel_service' => EnvVars::REDIS_SENTINEL_SERVICE->loadFromEnv(),
                ],
            ],
            ...$pubSub,
        ],
    };
})();
