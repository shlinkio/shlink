<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Config;

use function file_get_contents;
use function is_file;
use function Shlinkio\Shlink\Config\env;
use function Shlinkio\Shlink\Config\parseEnvVar;
use function sprintf;

enum EnvVars: string
{
    case DELETE_SHORT_URL_THRESHOLD = 'DELETE_SHORT_URL_THRESHOLD';
    case DB_DRIVER = 'DB_DRIVER';
    case DB_NAME = 'DB_NAME';
    case DB_USER = 'DB_USER';
    case DB_PASSWORD = 'DB_PASSWORD';
    case DB_HOST = 'DB_HOST';
    case DB_UNIX_SOCKET = 'DB_UNIX_SOCKET';
    case DB_PORT = 'DB_PORT';
    case GEOLITE_LICENSE_KEY = 'GEOLITE_LICENSE_KEY';
    case CACHE_NAMESPACE = 'CACHE_NAMESPACE';
    case REDIS_SERVERS = 'REDIS_SERVERS';
    case REDIS_SENTINEL_SERVICE = 'REDIS_SENTINEL_SERVICE';
    case REDIS_PUB_SUB_ENABLED = 'REDIS_PUB_SUB_ENABLED';
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
    case DEFAULT_QR_CODE_SIZE = 'DEFAULT_QR_CODE_SIZE';
    case DEFAULT_QR_CODE_MARGIN = 'DEFAULT_QR_CODE_MARGIN';
    case DEFAULT_QR_CODE_FORMAT = 'DEFAULT_QR_CODE_FORMAT';
    case DEFAULT_QR_CODE_ERROR_CORRECTION = 'DEFAULT_QR_CODE_ERROR_CORRECTION';
    case DEFAULT_QR_CODE_ROUND_BLOCK_SIZE = 'DEFAULT_QR_CODE_ROUND_BLOCK_SIZE';
    case QR_CODE_FOR_DISABLED_SHORT_URLS = 'QR_CODE_FOR_DISABLED_SHORT_URLS';
    case DEFAULT_QR_CODE_COLOR = 'DEFAULT_QR_CODE_COLOR';
    case DEFAULT_QR_CODE_BG_COLOR = 'DEFAULT_QR_CODE_BG_COLOR';
    case DEFAULT_QR_CODE_LOGO_URL = 'DEFAULT_QR_CODE_LOGO_URL';
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
    case REDIRECT_APPEND_EXTRA_PATH = 'REDIRECT_APPEND_EXTRA_PATH';
    case MULTI_SEGMENT_SLUGS_ENABLED = 'MULTI_SEGMENT_SLUGS_ENABLED';
    case ROBOTS_ALLOW_ALL_SHORT_URLS = 'ROBOTS_ALLOW_ALL_SHORT_URLS';
    case TIMEZONE = 'TIMEZONE';
    case MEMORY_LIMIT = 'MEMORY_LIMIT';

    public function loadFromEnv(mixed $default = null): mixed
    {
        return env($this->value) ?? $this->loadFromFileEnv() ?? $default;
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

    public function existsInEnv(): bool
    {
        return $this->loadFromEnv() !== null;
    }
}
