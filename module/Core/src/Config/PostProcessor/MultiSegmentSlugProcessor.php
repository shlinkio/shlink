<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Config\PostProcessor;

use function Functional\map;
use function str_replace;

class MultiSegmentSlugProcessor
{
    private const SINGLE_SEGMENT_PATTERN = '{shortCode}';
    private const MULTI_SEGMENT_PATTERN = '{shortCode:.+}';

    public function __invoke(array $config): array
    {
        $multiSegmentEnabled = $config['url_shortener']['multi_segment_slugs_enabled'] ?? false;
        if (! $multiSegmentEnabled) {
            return $config;
        }

        $config['routes'] = map($config['routes'] ?? [], static function (array $route): array {
            ['path' => $path] = $route;
            $route['path'] = str_replace(self::SINGLE_SEGMENT_PATTERN, self::MULTI_SEGMENT_PATTERN, $path);
            return $route;
        });

        return $config;
    }
}
