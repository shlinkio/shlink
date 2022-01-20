<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Config;

use ReflectionClass;
use ReflectionClassConstant;
use Shlinkio\Shlink\Core\Exception\InvalidArgumentException;

use function array_values;
use function Functional\contains;
use function Shlinkio\Shlink\Config\env;

// TODO Convert to enum

/**
 * @method static EnvVars DELETE_SHORT_URL_THRESHOLD()
 * @method static EnvVars DB_DRIVER()
 * @method static EnvVars DB_NAME()
 * @method static EnvVars DB_USER()
 * @method static EnvVars DB_PASSWORD()
 * @method static EnvVars DB_HOST()
 * @method static EnvVars DB_UNIX_SOCKET()
 * @method static EnvVars DB_PORT()
 * @method static EnvVars GEOLITE_LICENSE_KEY()
 * @method static EnvVars REDIS_SERVERS()
 * @method static EnvVars REDIS_SENTINEL_SERVICE()
 * @method static EnvVars MERCURE_PUBLIC_HUB_URL()
 * @method static EnvVars MERCURE_INTERNAL_HUB_URL()
 * @method static EnvVars MERCURE_JWT_SECRET()
 * @method static EnvVars DEFAULT_QR_CODE_SIZE()
 * @method static EnvVars DEFAULT_QR_CODE_MARGIN()
 * @method static EnvVars DEFAULT_QR_CODE_FORMAT()
 * @method static EnvVars DEFAULT_QR_CODE_ERROR_CORRECTION()
 * @method static EnvVars DEFAULT_QR_CODE_ROUND_BLOCK_SIZE()
 * @method static EnvVars RABBITMQ_ENABLED()
 * @method static EnvVars RABBITMQ_HOST()
 * @method static EnvVars RABBITMQ_PORT()
 * @method static EnvVars RABBITMQ_USER()
 * @method static EnvVars RABBITMQ_PASSWORD()
 * @method static EnvVars RABBITMQ_VHOST()
 * @method static EnvVars DEFAULT_INVALID_SHORT_URL_REDIRECT()
 * @method static EnvVars DEFAULT_REGULAR_404_REDIRECT()
 * @method static EnvVars DEFAULT_BASE_URL_REDIRECT()
 * @method static EnvVars REDIRECT_STATUS_CODE()
 * @method static EnvVars REDIRECT_CACHE_LIFETIME()
 * @method static EnvVars BASE_PATH()
 * @method static EnvVars PORT()
 * @method static EnvVars TASK_WORKER_NUM()
 * @method static EnvVars WEB_WORKER_NUM()
 * @method static EnvVars ANONYMIZE_REMOTE_ADDR()
 * @method static EnvVars TRACK_ORPHAN_VISITS()
 * @method static EnvVars DISABLE_TRACK_PARAM()
 * @method static EnvVars DISABLE_TRACKING()
 * @method static EnvVars DISABLE_IP_TRACKING()
 * @method static EnvVars DISABLE_REFERRER_TRACKING()
 * @method static EnvVars DISABLE_UA_TRACKING()
 * @method static EnvVars DISABLE_TRACKING_FROM()
 * @method static EnvVars DEFAULT_SHORT_CODES_LENGTH()
 * @method static EnvVars IS_HTTPS_ENABLED()
 * @method static EnvVars DEFAULT_DOMAIN()
 * @method static EnvVars AUTO_RESOLVE_TITLES()
 * @method static EnvVars REDIRECT_APPEND_EXTRA_PATH()
 * @method static EnvVars VISITS_WEBHOOKS()
 * @method static EnvVars NOTIFY_ORPHAN_VISITS_TO_WEBHOOKS()
 */
final class EnvVars
{
    public const DELETE_SHORT_URL_THRESHOLD = 'DELETE_SHORT_URL_THRESHOLD';
    public const DB_DRIVER = 'DB_DRIVER';
    public const DB_NAME = 'DB_NAME';
    public const DB_USER = 'DB_USER';
    public const DB_PASSWORD = 'DB_PASSWORD';
    public const DB_HOST = 'DB_HOST';
    public const DB_UNIX_SOCKET = 'DB_UNIX_SOCKET';
    public const DB_PORT = 'DB_PORT';
    public const GEOLITE_LICENSE_KEY = 'GEOLITE_LICENSE_KEY';
    public const REDIS_SERVERS = 'REDIS_SERVERS';
    public const REDIS_SENTINEL_SERVICE = 'REDIS_SENTINEL_SERVICE';
    public const MERCURE_PUBLIC_HUB_URL = 'MERCURE_PUBLIC_HUB_URL';
    public const MERCURE_INTERNAL_HUB_URL = 'MERCURE_INTERNAL_HUB_URL';
    public const MERCURE_JWT_SECRET = 'MERCURE_JWT_SECRET';
    public const DEFAULT_QR_CODE_SIZE = 'DEFAULT_QR_CODE_SIZE';
    public const DEFAULT_QR_CODE_MARGIN = 'DEFAULT_QR_CODE_MARGIN';
    public const DEFAULT_QR_CODE_FORMAT = 'DEFAULT_QR_CODE_FORMAT';
    public const DEFAULT_QR_CODE_ERROR_CORRECTION = 'DEFAULT_QR_CODE_ERROR_CORRECTION';
    public const DEFAULT_QR_CODE_ROUND_BLOCK_SIZE = 'DEFAULT_QR_CODE_ROUND_BLOCK_SIZE';
    public const RABBITMQ_ENABLED = 'RABBITMQ_ENABLED';
    public const RABBITMQ_HOST = 'RABBITMQ_HOST';
    public const RABBITMQ_PORT = 'RABBITMQ_PORT';
    public const RABBITMQ_USER = 'RABBITMQ_USER';
    public const RABBITMQ_PASSWORD = 'RABBITMQ_PASSWORD';
    public const RABBITMQ_VHOST = 'RABBITMQ_VHOST';
    public const DEFAULT_INVALID_SHORT_URL_REDIRECT = 'DEFAULT_INVALID_SHORT_URL_REDIRECT';
    public const DEFAULT_REGULAR_404_REDIRECT = 'DEFAULT_REGULAR_404_REDIRECT';
    public const DEFAULT_BASE_URL_REDIRECT = 'DEFAULT_BASE_URL_REDIRECT';
    public const REDIRECT_STATUS_CODE = 'REDIRECT_STATUS_CODE';
    public const REDIRECT_CACHE_LIFETIME = 'REDIRECT_CACHE_LIFETIME';
    public const BASE_PATH = 'BASE_PATH';
    public const PORT = 'PORT';
    public const TASK_WORKER_NUM = 'TASK_WORKER_NUM';
    public const WEB_WORKER_NUM = 'WEB_WORKER_NUM';
    public const ANONYMIZE_REMOTE_ADDR = 'ANONYMIZE_REMOTE_ADDR';
    public const TRACK_ORPHAN_VISITS = 'TRACK_ORPHAN_VISITS';
    public const DISABLE_TRACK_PARAM = 'DISABLE_TRACK_PARAM';
    public const DISABLE_TRACKING = 'DISABLE_TRACKING';
    public const DISABLE_IP_TRACKING = 'DISABLE_IP_TRACKING';
    public const DISABLE_REFERRER_TRACKING = 'DISABLE_REFERRER_TRACKING';
    public const DISABLE_UA_TRACKING = 'DISABLE_UA_TRACKING';
    public const DISABLE_TRACKING_FROM = 'DISABLE_TRACKING_FROM';
    public const DEFAULT_SHORT_CODES_LENGTH = 'DEFAULT_SHORT_CODES_LENGTH';
    public const IS_HTTPS_ENABLED = 'IS_HTTPS_ENABLED';
    public const DEFAULT_DOMAIN = 'DEFAULT_DOMAIN';
    public const AUTO_RESOLVE_TITLES = 'AUTO_RESOLVE_TITLES';
    public const REDIRECT_APPEND_EXTRA_PATH = 'REDIRECT_APPEND_EXTRA_PATH';
    public const VISITS_WEBHOOKS = 'VISITS_WEBHOOKS';
    public const NOTIFY_ORPHAN_VISITS_TO_WEBHOOKS = 'NOTIFY_ORPHAN_VISITS_TO_WEBHOOKS';

    /**
     * @return string[]
     */
    public static function cases(): array
    {
        static $constants;
        if ($constants !== null) {
            return $constants;
        }

        $ref = new ReflectionClass(self::class);
        return $constants = array_values($ref->getConstants(ReflectionClassConstant::IS_PUBLIC));
    }

    private function __construct(private string $envVar)
    {
    }

    public static function __callStatic(string $name, array $arguments): self
    {
        if (! contains(self::cases(), $name)) {
            throw new InvalidArgumentException('Invalid env var: "' . $name . '"');
        }

        return new self($name);
    }

    public function loadFromEnv(mixed $default = null): mixed
    {
        return env($this->envVar, $default);
    }

    public function existsInEnv(): bool
    {
        return $this->loadFromEnv() !== null;
    }
}
