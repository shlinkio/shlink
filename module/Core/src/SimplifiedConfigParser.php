<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core;

use Shlinkio\Shlink\Installer\Util\PathCollection;
use Zend\Stdlib\ArrayUtils;

use function array_intersect_key;
use function array_key_exists;
use function Functional\contains;
use function Functional\reduce_left;

class SimplifiedConfigParser
{
    private const SIMPLIFIED_CONFIG_MAPPING = [
        'disable_track_param' => ['app_options', 'disable_track_param'],
        'short_domain_schema' => ['url_shortener', 'domain', 'schema'],
        'short_domain_host' => ['url_shortener', 'domain', 'hostname'],
        'validate_url' => ['url_shortener', 'validate_url'],
        'not_found_redirect_to' => ['url_shortener', 'not_found_short_url', 'redirect_to'],
        'db_config' => ['entity_manager', 'connection'],
        'delete_short_url_threshold' => ['delete_short_urls', 'visits_threshold'],
        'redis_servers' => ['redis', 'servers'],
    ];
    private const SIMPLIFIED_CONFIG_SIDE_EFFECTS = [
        'not_found_redirect_to' => [
            'path' => ['url_shortener', 'not_found_short_url', 'enable_redirection'],
            'value' => true,
        ],
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
        $existingKeys = array_intersect_key($config, self::SIMPLIFIED_CONFIG_MAPPING);

        return reduce_left($existingKeys, function ($value, string $key, $c, PathCollection $collection) {
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
}
