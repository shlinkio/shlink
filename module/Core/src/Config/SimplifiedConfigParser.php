<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Config;

use Shlinkio\Shlink\Installer\Util\PathCollection;
use Zend\Stdlib\ArrayUtils;

use function array_flip;
use function array_intersect_key;
use function array_key_exists;
use function array_keys;
use function Functional\contains;
use function Functional\reduce_left;
use function uksort;

class SimplifiedConfigParser
{
    private const SIMPLIFIED_CONFIG_MAPPING = [
        'disable_track_param' => ['app_options', 'disable_track_param'],
        'short_domain_schema' => ['url_shortener', 'domain', 'schema'],
        'short_domain_host' => ['url_shortener', 'domain', 'hostname'],
        'validate_url' => ['url_shortener', 'validate_url'],
        'not_found_redirect_to' => ['not_found_redirects', 'invalid_short_url'], // Deprecated
        'invalid_short_url_redirect_to' => ['not_found_redirects', 'invalid_short_url'],
        'regular_404_redirect_to' => ['not_found_redirects', 'regular_404'],
        'base_url_redirect_to' => ['not_found_redirects', 'base_path'],
        'db_config' => ['entity_manager', 'connection'],
        'delete_short_url_threshold' => ['delete_short_urls', 'visits_threshold'],
        'redis_servers' => ['redis', 'servers'],
        'base_path' => ['router', 'base_path'],
        'web_worker_num' => ['zend-expressive-swoole', 'swoole-http-server', 'options', 'worker_num'],
        'task_worker_num' => ['zend-expressive-swoole', 'swoole-http-server', 'options', 'task_worker_num'],
        'visits_webhooks' => ['url_shortener', 'visits_webhooks'],
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
        uksort($configForExistingKeys, function (string $a, string $b) use ($simplifiedConfigOrder): int {
            return $simplifiedConfigOrder[$a] - $simplifiedConfigOrder[$b];
        });

        return $configForExistingKeys;
    }
}
