<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Config;

use Laminas\Stdlib\ArrayUtils;
use Shlinkio\Shlink\Config\Collection\PathCollection;

use function array_flip;
use function array_intersect_key;
use function array_key_exists;
use function array_keys;
use function Functional\contains;
use function Functional\reduce_left;
use function uksort;

/** @deprecated */
class SimplifiedConfigParser
{
    private const SIMPLIFIED_CONFIG_MAPPING = [
        'disable_track_param' => ['app_options', 'disable_track_param'],
        'short_domain_schema' => ['url_shortener', 'domain', 'schema'],
        'short_domain_host' => ['url_shortener', 'domain', 'hostname'],
        'validate_url' => ['url_shortener', 'validate_url'],
        'invalid_short_url_redirect_to' => ['not_found_redirects', 'invalid_short_url'],
        'regular_404_redirect_to' => ['not_found_redirects', 'regular_404'],
        'base_url_redirect_to' => ['not_found_redirects', 'base_url'],
        'db_config' => ['entity_manager', 'connection'],
        'delete_short_url_threshold' => ['delete_short_urls', 'visits_threshold'],
        'redis_servers' => ['cache', 'redis', 'servers'],
        'base_path' => ['router', 'base_path'],
        'web_worker_num' => ['mezzio-swoole', 'swoole-http-server', 'options', 'worker_num'],
        'task_worker_num' => ['mezzio-swoole', 'swoole-http-server', 'options', 'task_worker_num'],
        'visits_webhooks' => ['url_shortener', 'visits_webhooks'],
        'default_short_codes_length' => ['url_shortener', 'default_short_codes_length'],
        'geolite_license_key' => ['geolite2', 'license_key'],
        'mercure_public_hub_url' => ['mercure', 'public_hub_url'],
        'mercure_internal_hub_url' => ['mercure', 'internal_hub_url'],
        'mercure_jwt_secret' => ['mercure', 'jwt_secret'],
        'anonymize_remote_addr' => ['url_shortener', 'anonymize_remote_addr'],
        'redirect_status_code' => ['url_shortener', 'redirect_status_code'],
        'redirect_cache_lifetime' => ['url_shortener', 'redirect_cache_lifetime'],
        'port' => ['mezzio-swoole', 'swoole-http-server', 'port'],
    ];
    private const SIMPLIFIED_CONFIG_SIDE_EFFECTS = [
        'delete_short_url_threshold' => [
            'path' => ['delete_short_urls', 'check_visits_threshold'],
            'value' => true,
        ],
        'redis_servers' => [
            'path' => ['dependencies', 'aliases', 'lock_store'],
            'value' => 'redis_lock_store',
        ],
    ];
    private const SIMPLIFIED_MERGEABLE_CONFIG = ['db_config'];

    public function __invoke(array $config): array
    {
        $configForExistingKeys = $this->getConfigForKeysInMappingOrderedByMapping($config);

        return reduce_left($configForExistingKeys, function ($value, string $key, $c, PathCollection $collection) {
            $path = self::SIMPLIFIED_CONFIG_MAPPING[$key];
            if (contains(self::SIMPLIFIED_MERGEABLE_CONFIG, $key)) {
                $value = ArrayUtils::merge($collection->getValueInPath($path), $value);
            }

            $collection->setValueInPath($value, $path);
            if (array_key_exists($key, self::SIMPLIFIED_CONFIG_SIDE_EFFECTS)) {
                ['path' => $sideEffectPath, 'value' => $sideEffectValue] = self::SIMPLIFIED_CONFIG_SIDE_EFFECTS[$key];
                $collection->setValueInPath($sideEffectValue, $sideEffectPath);
            }

            return $collection;
        }, new PathCollection($config))->toArray();
    }

    private function getConfigForKeysInMappingOrderedByMapping(array $config): array
    {
        // Ignore any config which is not defined in the mapping
        $configForExistingKeys = array_intersect_key($config, self::SIMPLIFIED_CONFIG_MAPPING);

        // Order the config by their key, based on the order it was defined in the mapping.
        // This mainly allows deprecating keys and defining new ones that will replace the older and always take
        // preference, while the old one keeps working for backwards compatibility if the new one is not provided.
        $simplifiedConfigOrder = array_flip(array_keys(self::SIMPLIFIED_CONFIG_MAPPING));
        uksort(
            $configForExistingKeys,
            fn (string $a, string $b): int => $simplifiedConfigOrder[$a] - $simplifiedConfigOrder[$b],
        );

        return $configForExistingKeys;
    }
}
