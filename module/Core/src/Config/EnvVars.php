<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Config;

use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlMode;

use function date_default_timezone_get;
use function file_get_contents;
use function is_file;
use function Shlinkio\Shlink\Config\env;
use function Shlinkio\Shlink\Config\parseEnvVar;
use function sprintf;

use const Shlinkio\Shlink\DEFAULT_QR_CODE_BG_COLOR;
use const Shlinkio\Shlink\DEFAULT_QR_CODE_COLOR;
use const Shlinkio\Shlink\DEFAULT_QR_CODE_ENABLED_FOR_DISABLED_SHORT_URLS;
use const Shlinkio\Shlink\DEFAULT_QR_CODE_ERROR_CORRECTION;
use const Shlinkio\Shlink\DEFAULT_QR_CODE_FORMAT;
use const Shlinkio\Shlink\DEFAULT_QR_CODE_MARGIN;
use const Shlinkio\Shlink\DEFAULT_QR_CODE_ROUND_BLOCK_SIZE;
use const Shlinkio\Shlink\DEFAULT_QR_CODE_SIZE;
use const Shlinkio\Shlink\DEFAULT_REDIRECT_CACHE_LIFETIME;
use const Shlinkio\Shlink\DEFAULT_REDIRECT_STATUS_CODE;
use const Shlinkio\Shlink\DEFAULT_SHORT_CODES_LENGTH;

enum EnvVars: string
{
    case APP_ENV = 'APP_ENV';
    case DELETE_SHORT_URL_THRESHOLD = 'DELETE_SHORT_URL_THRESHOLD';
    case DB_DRIVER = 'DB_DRIVER';
    case DB_NAME = 'DB_NAME';
    case DB_USER = 'DB_USER';
    case DB_PASSWORD = 'DB_PASSWORD';
    case DB_HOST = 'DB_HOST';
    case DB_UNIX_SOCKET = 'DB_UNIX_SOCKET';
    case DB_PORT = 'DB_PORT';
    case DB_USE_ENCRYPTION = 'DB_USE_ENCRYPTION';
    case GEOLITE_LICENSE_KEY = 'GEOLITE_LICENSE_KEY';
    case CACHE_NAMESPACE = 'CACHE_NAMESPACE';
    case REDIS_SERVERS = 'REDIS_SERVERS';
    case REDIS_SENTINEL_SERVICE = 'REDIS_SENTINEL_SERVICE';
    case REDIS_PUB_SUB_ENABLED = 'REDIS_PUB_SUB_ENABLED';
    case MERCURE_ENABLED = 'MERCURE_ENABLED';
    case MERCURE_PUBLIC_HUB_URL = 'MERCURE_PUBLIC_HUB_URL';
    case MERCURE_INTERNAL_HUB_URL = 'MERCURE_INTERNAL_HUB_URL';
    case MERCURE_JWT_SECRET = 'MERCURE_JWT_SECRET';
    case RABBITMQ_ENABLED = 'RABBITMQ_ENABLED';
    case RABBITMQ_HOST = 'RABBITMQ_HOST';
    case RABBITMQ_PORT = 'RABBITMQ_PORT';
    case RABBITMQ_USER = 'RABBITMQ_USER';
    case RABBITMQ_PASSWORD = 'RABBITMQ_PASSWORD';
    case RABBITMQ_VHOST = 'RABBITMQ_VHOST';
    case RABBITMQ_USE_SSL = 'RABBITMQ_USE_SSL';
    case MATOMO_ENABLED = 'MATOMO_ENABLED';
    case MATOMO_BASE_URL = 'MATOMO_BASE_URL';
    case MATOMO_SITE_ID = 'MATOMO_SITE_ID';
    case MATOMO_API_TOKEN = 'MATOMO_API_TOKEN';
    case DEFAULT_INVALID_SHORT_URL_REDIRECT = 'DEFAULT_INVALID_SHORT_URL_REDIRECT';
    case DEFAULT_REGULAR_404_REDIRECT = 'DEFAULT_REGULAR_404_REDIRECT';
    case DEFAULT_BASE_URL_REDIRECT = 'DEFAULT_BASE_URL_REDIRECT';
    case REDIRECT_STATUS_CODE = 'REDIRECT_STATUS_CODE';
    case REDIRECT_CACHE_LIFETIME = 'REDIRECT_CACHE_LIFETIME';
    case BASE_PATH = 'BASE_PATH';
    case SHORT_URL_TRAILING_SLASH = 'SHORT_URL_TRAILING_SLASH';
    case SHORT_URL_MODE = 'SHORT_URL_MODE';
    case ANONYMIZE_REMOTE_ADDR = 'ANONYMIZE_REMOTE_ADDR';
    case TRACK_ORPHAN_VISITS = 'TRACK_ORPHAN_VISITS';
    case DISABLE_TRACK_PARAM = 'DISABLE_TRACK_PARAM';
    case DISABLE_TRACKING = 'DISABLE_TRACKING';
    case DISABLE_IP_TRACKING = 'DISABLE_IP_TRACKING';
    case DISABLE_REFERRER_TRACKING = 'DISABLE_REFERRER_TRACKING';
    case DISABLE_UA_TRACKING = 'DISABLE_UA_TRACKING';
    case DISABLE_TRACKING_FROM = 'DISABLE_TRACKING_FROM';
    case DEFAULT_SHORT_CODES_LENGTH = 'DEFAULT_SHORT_CODES_LENGTH';
    case IS_HTTPS_ENABLED = 'IS_HTTPS_ENABLED';
    case DEFAULT_DOMAIN = 'DEFAULT_DOMAIN';
    case AUTO_RESOLVE_TITLES = 'AUTO_RESOLVE_TITLES';
    case REDIRECT_EXTRA_PATH_MODE = 'REDIRECT_EXTRA_PATH_MODE';
    case MULTI_SEGMENT_SLUGS_ENABLED = 'MULTI_SEGMENT_SLUGS_ENABLED';
    case ROBOTS_ALLOW_ALL_SHORT_URLS = 'ROBOTS_ALLOW_ALL_SHORT_URLS';
    case ROBOTS_USER_AGENTS = 'ROBOTS_USER_AGENTS';
    case TIMEZONE = 'TIMEZONE';
    case MEMORY_LIMIT = 'MEMORY_LIMIT';
    case INITIAL_API_KEY = 'INITIAL_API_KEY';
    case SKIP_INITIAL_GEOLITE_DOWNLOAD = 'SKIP_INITIAL_GEOLITE_DOWNLOAD';
    case REAL_TIME_UPDATES_TOPICS = 'REAL_TIME_UPDATES_TOPICS';
    case CORS_ALLOW_ORIGIN = 'CORS_ALLOW_ORIGIN';
    case CORS_ALLOW_CREDENTIALS = 'CORS_ALLOW_CREDENTIALS';
    case CORS_MAX_AGE = 'CORS_MAX_AGE';

    /** @deprecated Use REDIRECT_EXTRA_PATH */
    case REDIRECT_APPEND_EXTRA_PATH = 'REDIRECT_APPEND_EXTRA_PATH';
    /** @deprecated */
    case DEFAULT_QR_CODE_SIZE = 'DEFAULT_QR_CODE_SIZE';
    /** @deprecated */
    case DEFAULT_QR_CODE_MARGIN = 'DEFAULT_QR_CODE_MARGIN';
    /** @deprecated */
    case DEFAULT_QR_CODE_FORMAT = 'DEFAULT_QR_CODE_FORMAT';
    /** @deprecated */
    case DEFAULT_QR_CODE_ERROR_CORRECTION = 'DEFAULT_QR_CODE_ERROR_CORRECTION';
    /** @deprecated */
    case DEFAULT_QR_CODE_ROUND_BLOCK_SIZE = 'DEFAULT_QR_CODE_ROUND_BLOCK_SIZE';
    /** @deprecated */
    case QR_CODE_FOR_DISABLED_SHORT_URLS = 'QR_CODE_FOR_DISABLED_SHORT_URLS';
    /** @deprecated */
    case DEFAULT_QR_CODE_COLOR = 'DEFAULT_QR_CODE_COLOR';
    /** @deprecated */
    case DEFAULT_QR_CODE_BG_COLOR = 'DEFAULT_QR_CODE_BG_COLOR';
    /** @deprecated */
    case DEFAULT_QR_CODE_LOGO_URL = 'DEFAULT_QR_CODE_LOGO_URL';

    public function loadFromEnv(): mixed
    {
        return env($this->value) ?? $this->loadFromFileEnv() ?? $this->defaultValue();
    }

    /**
     * Checks if an equivalent environment variable exists with the `_FILE` suffix. If so, it loads its value as a file,
     * reads it, and returns its contents.
     * This is useful when loading Shlink with docker compose and using secrets.
     * See https://docs.docker.com/compose/use-secrets/
     */
    private function loadFromFileEnv(): string|int|bool|null
    {
        $file = env(sprintf('%s_FILE', $this->value));
        if ($file === null || ! is_file($file)) {
            return null;
        }

        $content = file_get_contents($file);
        return $content ? parseEnvVar($content) : null;
    }

    private function defaultValue(): string|int|bool|null
    {
        return match ($this) {
            self::APP_ENV => 'prod',
            self::MEMORY_LIMIT => '512M',
            self::TIMEZONE => date_default_timezone_get(),

            self::DEFAULT_SHORT_CODES_LENGTH => DEFAULT_SHORT_CODES_LENGTH,
            self::SHORT_URL_MODE => ShortUrlMode::STRICT->value,
            self::IS_HTTPS_ENABLED, self::AUTO_RESOLVE_TITLES => true,
            self::MULTI_SEGMENT_SLUGS_ENABLED,
            self::SHORT_URL_TRAILING_SLASH => false,
            self::DEFAULT_DOMAIN, self::BASE_PATH => '',
            self::CACHE_NAMESPACE => 'Shlink',
            // Deprecated. In Shlink 5.0.0, add default value for REDIRECT_EXTRA_PATH_MODE
            self::REDIRECT_APPEND_EXTRA_PATH => false,
            // self::REDIRECT_EXTRA_PATH_MODE => ExtraPathMode::DEFAULT->value,

            self::REDIS_PUB_SUB_ENABLED,
            self::MATOMO_ENABLED,
            self::ROBOTS_ALLOW_ALL_SHORT_URLS => false,

            self::DB_NAME => 'shlink',
            self::DB_HOST => self::DB_UNIX_SOCKET->loadFromEnv(),
            self::DB_DRIVER => 'sqlite',
            self::DB_PORT => match (self::DB_DRIVER->loadFromEnv()) {
                'postgres' => '5432',
                'mssql' => '1433',
                default => '3306',
            },
            self::DB_USE_ENCRYPTION => false,

            self::MERCURE_ENABLED => self::MERCURE_PUBLIC_HUB_URL->existsInEnv(),
            self::MERCURE_INTERNAL_HUB_URL => self::MERCURE_PUBLIC_HUB_URL->loadFromEnv(),

            self::DEFAULT_QR_CODE_SIZE, => DEFAULT_QR_CODE_SIZE,
            self::DEFAULT_QR_CODE_MARGIN, => DEFAULT_QR_CODE_MARGIN,
            self::DEFAULT_QR_CODE_FORMAT, => DEFAULT_QR_CODE_FORMAT,
            self::DEFAULT_QR_CODE_ERROR_CORRECTION, => DEFAULT_QR_CODE_ERROR_CORRECTION,
            self::DEFAULT_QR_CODE_ROUND_BLOCK_SIZE, => DEFAULT_QR_CODE_ROUND_BLOCK_SIZE,
            self::QR_CODE_FOR_DISABLED_SHORT_URLS, => DEFAULT_QR_CODE_ENABLED_FOR_DISABLED_SHORT_URLS,
            self::DEFAULT_QR_CODE_COLOR, => DEFAULT_QR_CODE_COLOR,
            self::DEFAULT_QR_CODE_BG_COLOR, => DEFAULT_QR_CODE_BG_COLOR,

            self::RABBITMQ_ENABLED, self::RABBITMQ_USE_SSL => false,
            self::RABBITMQ_PORT => 5672,
            self::RABBITMQ_VHOST => '/',

            self::REDIRECT_STATUS_CODE => DEFAULT_REDIRECT_STATUS_CODE->value,
            self::REDIRECT_CACHE_LIFETIME => DEFAULT_REDIRECT_CACHE_LIFETIME,

            self::ANONYMIZE_REMOTE_ADDR, self::TRACK_ORPHAN_VISITS => true,
            self::DISABLE_TRACKING,
            self::DISABLE_IP_TRACKING,
            self::DISABLE_REFERRER_TRACKING,
            self::DISABLE_UA_TRACKING => false,

            self::CORS_ALLOW_ORIGIN => '*',
            self::CORS_ALLOW_CREDENTIALS => false,
            self::CORS_MAX_AGE => 3600,

            default => null,
        };
    }

    public function existsInEnv(): bool
    {
        return $this->loadFromEnv() !== null;
    }

    public static function isProdEnv(): bool
    {
        return self::APP_ENV->loadFromEnv() === 'prod';
    }

    public static function isDevEnv(): bool
    {
        return self::APP_ENV->loadFromEnv() === 'dev';
    }

    public static function isTestEnv(): bool
    {
        return self::APP_ENV->loadFromEnv() === 'test';
    }
}
