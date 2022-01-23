<?php

declare(strict_types=1);

use Shlinkio\Shlink\Core\Config\EnvVars;

return (static function (): array {
    $redisServers = EnvVars::REDIS_SERVERS()->loadFromEnv();

    return match ($redisServers) {
        null => [],
        default => [
            'cache' => [
                'redis' => [
                    'servers' => $redisServers,
                    'sentinel_service' => EnvVars::REDIS_SENTINEL_SERVICE()->loadFromEnv(),
                ],
            ],
        ],
    };
})();
