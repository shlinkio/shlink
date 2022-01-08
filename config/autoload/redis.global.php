<?php

declare(strict_types=1);

use function Shlinkio\Shlink\Config\env;

return (static function (): array {
    $redisServers = env('REDIS_SERVERS');

    return match ($redisServers) {
        null => [],
        default => [
            'cache' => [
                'default_lifetime' => 86400, // 24h
                'redis' => [
                    'servers' => $redisServers,
                    'sentinel_service' => env('REDIS_SENTINEL_SERVICE'),
                ],
            ],
        ],
    };
})();
