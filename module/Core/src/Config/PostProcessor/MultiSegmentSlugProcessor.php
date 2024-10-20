<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Config\PostProcessor;

use Shlinkio\Shlink\Core\Config\EnvVars;

use function array_map;
use function str_replace;

class MultiSegmentSlugProcessor
{
    private const SINGLE_SEGMENT_PATTERN = '{shortCode}';
    private const MULTI_SEGMENT_PATTERN = '{shortCode:.+}';

    public function __invoke(array $config): array
    {
        $multiSegmentEnabled = (bool) EnvVars::MULTI_SEGMENT_SLUGS_ENABLED->loadFromEnv();
        if (! $multiSegmentEnabled) {
            return $config;
        }

        $config['routes'] = array_map(static function (array $route): array {
            ['path' => $path] = $route;
            $route['path'] = str_replace(self::SINGLE_SEGMENT_PATTERN, self::MULTI_SEGMENT_PATTERN, $path);
            return $route;
        }, $config['routes'] ?? []);

        return $config;
    }
}
