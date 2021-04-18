<?php

declare(strict_types=1);

namespace Shlinkio\Shlink;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

use function explode;
use function Functional\contains;
use function Shlinkio\Shlink\Common\env;

use const Shlinkio\Shlink\Core\DEFAULT_DELETE_SHORT_URL_THRESHOLD;
use const Shlinkio\Shlink\Core\DEFAULT_REDIRECT_CACHE_LIFETIME;
use const Shlinkio\Shlink\Core\DEFAULT_REDIRECT_STATUS_CODE;
use const Shlinkio\Shlink\Core\DEFAULT_SHORT_CODES_LENGTH;
use const Shlinkio\Shlink\Core\MIN_SHORT_CODES_LENGTH;

$helper = new class {
    private const DB_DRIVERS_MAP = [
        'mysql' => 'pdo_mysql',
        'maria' => 'pdo_mysql',
        'postgres' => 'pdo_pgsql',
        'mssql' => 'pdo_sqlsrv',
    ];
    private const DB_PORTS_MAP = [
        'mysql' => '3306',
        'maria' => '3306',
        'postgres' => '5432',
        'mssql' => '1433',
    ];

    public function getDbConfig(): array
    {
        $driver = env('DB_DRIVER');
        $isMysql = contains(['maria', 'mysql'], $driver);
        if ($driver === null || $driver === 'sqlite') {
            return [
                'driver' => 'pdo_sqlite',
                'path' => 'data/database.sqlite',
            ];
        }

        return [
            'driver' => self::DB_DRIVERS_MAP[$driver],
            'dbname' => env('DB_NAME', 'shlink'),
            'user' => env('DB_USER'),
            'password' => env('DB_PASSWORD'),
            'host' => env('DB_HOST', $driver === 'postgres' ? env('DB_UNIX_SOCKET') : null),
            'port' => env('DB_PORT', self::DB_PORTS_MAP[$driver]),
            'unix_socket' => $isMysql ? env('DB_UNIX_SOCKET') : null,
        ];
    }

    public function getNotFoundRedirectsConfig(): array
    {
        return [
            'invalid_short_url' => env('INVALID_SHORT_URL_REDIRECT_TO'),
            'regular_404' => env('REGULAR_404_REDIRECT_TO'),
            'base_url' => env('BASE_URL_REDIRECT_TO'),
        ];
    }

    public function getVisitsWebhooks(): array
    {
        $webhooks = env('VISITS_WEBHOOKS');
        return $webhooks === null ? [] : explode(',', $webhooks);
    }

    public function getRedisConfig(): ?array
    {
        $redisServers = env('REDIS_SERVERS');
        return $redisServers === null ? null : ['servers' => $redisServers];
    }

    public function getDefaultShortCodesLength(): int
    {
        $value = (int) env('DEFAULT_SHORT_CODES_LENGTH', DEFAULT_SHORT_CODES_LENGTH);
        return $value < MIN_SHORT_CODES_LENGTH ? MIN_SHORT_CODES_LENGTH : $value;
    }

    public function getMercureConfig(): array
    {
        $publicUrl = env('MERCURE_PUBLIC_HUB_URL');

        return [
            'public_hub_url' => $publicUrl,
            'internal_hub_url' => env('MERCURE_INTERNAL_HUB_URL', $publicUrl),
            'jwt_secret' => env('MERCURE_JWT_SECRET'),
        ];
    }
};

return [

    'app_options' => [
        'disable_track_param' => env('DISABLE_TRACK_PARAM'),
    ],

    'delete_short_urls' => [
        'check_visits_threshold' => true,
        'visits_threshold' => (int) env('DELETE_SHORT_URL_THRESHOLD', DEFAULT_DELETE_SHORT_URL_THRESHOLD),
    ],

    'entity_manager' => [
        'connection' => $helper->getDbConfig(),
    ],

    'url_shortener' => [
        'domain' => [
            'schema' => env('SHORT_DOMAIN_SCHEMA', 'http'),
            'hostname' => env('SHORT_DOMAIN_HOST', ''),
        ],
        'validate_url' => (bool) env('VALIDATE_URLS', false),
        'anonymize_remote_addr' => (bool) env('ANONYMIZE_REMOTE_ADDR', true),
        'visits_webhooks' => $helper->getVisitsWebhooks(),
        'default_short_codes_length' => $helper->getDefaultShortCodesLength(),
        'redirect_status_code' => (int) env('REDIRECT_STATUS_CODE', DEFAULT_REDIRECT_STATUS_CODE),
        'redirect_cache_lifetime' => (int) env('REDIRECT_CACHE_LIFETIME', DEFAULT_REDIRECT_CACHE_LIFETIME),
        'auto_resolve_titles' => (bool) env('AUTO_RESOLVE_TITLES', false),
        'track_orphan_visits' => (bool) env('TRACK_ORPHAN_VISITS', true),
    ],

    'not_found_redirects' => $helper->getNotFoundRedirectsConfig(),

    'logger' => [
        'Shlink' => [
            'handlers' => [
                'shlink_handler' => [
                    'name' => StreamHandler::class,
                    'params' => [
                        'level' => Logger::INFO,
                        'stream' => 'php://stdout',
                    ],
                ],
            ],
        ],
    ],

    'dependencies' => [
        'aliases' => env('REDIS_SERVERS') === null ? [] : [
            'lock_store' => 'redis_lock_store',
        ],
    ],

    'cache' => [
        'redis' => $helper->getRedisConfig(),
    ],

    'router' => [
        'base_path' => env('BASE_PATH', ''),
    ],

    'mezzio-swoole' => [
        'swoole-http-server' => [
            'port' => (int) env('PORT', 8080),
            'options' => [
                'worker_num' => (int) env('WEB_WORKER_NUM', 16),
                'task_worker_num' => (int) env('TASK_WORKER_NUM', 16),
            ],
        ],
    ],

    'geolite2' => [
        'license_key' => env('GEOLITE_LICENSE_KEY', 'G4Lm0C60yJsnkdPi'), // Deprecated. Remove the default value
    ],

    'mercure' => $helper->getMercureConfig(),

];
