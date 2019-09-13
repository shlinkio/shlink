<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Config;

use function Functional\map;

class BasePathPrefixer
{
    public function __invoke(array $config): array
    {
        $basePath = $config['router']['base_path'] ?? '';
        $config['routes'] = $this->prefixRoutesWithBasePath($config, $basePath);
        $config['middleware_pipeline'] = $this->prefixMiddlewarePathsWithBasePath($config, $basePath);
        $config['url_shortener']['domain']['hostname'] .= $basePath;

        return $config;
    }

    private function prefixRoutesWithBasePath(array $config, string $basePath): array
    {
        return map($config['routes'] ?? [], function (array $route) use ($basePath) {
            $route['path'] = $basePath . $route['path'];
            return $route;
        });
    }

    private function prefixMiddlewarePathsWithBasePath(array $config, string $basePath): array
    {
        return map($config['middleware_pipeline'] ?? [], function (array $middleware) use ($basePath) {
            if (! isset($middleware['path'])) {
                return $middleware;
            }

            $middleware['path'] = $basePath . $middleware['path'];
            return $middleware;
        });
    }
}
