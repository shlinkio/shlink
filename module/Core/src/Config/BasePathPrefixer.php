<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Config;

use function Functional\map;

class BasePathPrefixer
{
    private const ELEMENTS_WITH_PATH = ['routes', 'middleware_pipeline'];

    public function __invoke(array $config): array
    {
        $basePath = $config['router']['base_path'] ?? '';
        $config['url_shortener']['domain']['hostname'] .= $basePath;

        foreach (self::ELEMENTS_WITH_PATH as $configKey) {
            $config[$configKey] = $this->prefixPathsWithBasePath($configKey, $config, $basePath);
        }

        return $config;
    }

    private function prefixPathsWithBasePath(string $configKey, array $config, string $basePath): array
    {
        return map($config[$configKey] ?? [], function (array $element) use ($basePath) {
            if (! isset($element['path'])) {
                return $element;
            }

            $element['path'] = $basePath . $element['path'];
            return $element;
        });
    }
}
