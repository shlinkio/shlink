<?php
declare(strict_types=1);

namespace Shlinkio\Shlink;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

use function explode;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function implode;
use function Shlinkio\Shlink\Common\env;
use function sprintf;
use function str_shuffle;
use function substr;
use function sys_get_temp_dir;

$helper = new class {
    private const BASE62 = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    private const DB_DRIVERS_MAP = [
        'mysql' => 'pdo_mysql',
        'postgres' => 'pdo_pgsql',
    ];
    private const DB_PORTS_MAP = [
        'mysql' => '3306',
        'postgres' => '5432',
    ];

    /** @var string */
    private $charset;
    /** @var string */
    private $secretKey;

    public function __construct()
    {
        [$this->charset, $this->secretKey] = $this->initShlinkKeys();
    }

    private function initShlinkKeys(): array
    {
        $keysFile = sprintf('%s/shlink.keys', sys_get_temp_dir());
        if (file_exists($keysFile)) {
            return explode(',', file_get_contents($keysFile));
        }

        $keys = [
            env('SHORTCODE_CHARS', $this->generateShortcodeChars()),
            env('SECRET_KEY', $this->generateSecretKey()),
        ];

        file_put_contents($keysFile, implode(',', $keys));
        return $keys;
    }

    private function generateShortcodeChars(): string
    {
        return str_shuffle(self::BASE62);
    }

    private function generateSecretKey(): string
    {
        return substr(str_shuffle(self::BASE62), 0, 32);
    }

    public function getShortcodeChars(): string
    {
        return $this->charset;
    }

    public function getSecretKey(): string
    {
        return $this->secretKey;
    }

    public function getDbConfig(): array
    {
        $driver = env('DB_DRIVER');
        if ($driver === null || $driver === 'sqlite') {
            return [
                'driver' => 'pdo_sqlite',
                'path' => 'data/database.sqlite',
            ];
        }

        $driverOptions = $driver !== 'mysql' ? [] : [
            // PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
            1002 => 'SET NAMES utf8',
        ];
        return [
            'driver' => self::DB_DRIVERS_MAP[$driver],
            'dbname' => env('DB_NAME', 'shlink'),
            'user' => env('DB_USER'),
            'password' => env('DB_PASSWORD'),
            'host' => env('DB_HOST'),
            'port' => env('DB_PORT', self::DB_PORTS_MAP[$driver]),
            'driverOptions' => $driverOptions,
        ];
    }

    public function getNotFoundConfig(): array
    {
        $notFoundRedirectTo = env('NOT_FOUND_REDIRECT_TO');

        return [
            'enable_redirection' => $notFoundRedirectTo !== null,
            'redirect_to' => $notFoundRedirectTo,
        ];
    }
};

return [

    'config_cache_enabled' => false,

    'app_options' => [
        'secret_key' => $helper->getSecretKey(),
        'disable_track_param' => env('DISABLE_TRACK_PARAM'),
    ],

    'delete_short_urls' => [
        'check_visits_threshold' => true,
        'visits_threshold' => (int) env('DELETE_SHORT_URL_THRESHOLD', 15),
    ],

    'translator' => [
        'locale' => env('LOCALE', 'en'),
    ],

    'entity_manager' => [
        'connection' => $helper->getDbConfig(),
    ],

    'url_shortener' => [
        'domain' => [
            'schema' => env('SHORT_DOMAIN_SCHEMA', 'http'),
            'hostname' => env('SHORT_DOMAIN_HOST', ''),
        ],
        'shortcode_chars' => $helper->getShortcodeChars(),
        'validate_url' => (bool) env('VALIDATE_URLS', true),
        'not_found_short_url' => $helper->getNotFoundConfig(),
    ],

    'logger' => [
        'handlers' => [
            'shlink_rotating_handler' => [
                'level' => Logger::EMERGENCY, // This basically disables regular file logs
            ],
            'shlink_stdout_handler' => [
                'class' => StreamHandler::class,
                'level' => Logger::INFO,
                'stream' => 'php://stdout',
                'formatter' => 'dashed',
            ],
        ],

        'loggers' => [
            'Shlink' => [
                'handlers' => ['shlink_stdout_handler'],
            ],
        ],
    ],

    'dependencies' => [
        'aliases' => env('REDIS_SERVERS') === null ? [] : [
            'lock_store' => 'redis_lock_store',
        ],
    ],

    'redis' => [
        'servers' => env('REDIS_SERVERS'),
    ],

];
