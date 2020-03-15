<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest;

use Closure;

use function Functional\first;
use function Functional\map;
use function Shlinkio\Shlink\Config\loadConfigFromGlob;
use function sprintf;

class ConfigProvider
{
    private const ROUTES_PREFIX = '/rest/v{version:1|2}';
    private const UNVERSIONED_ROUTES_PREFIX = '/rest';
    public const UNVERSIONED_HEALTH_ENDPOINT_NAME = 'unversioned_health';

    private Closure $loadConfig;

    public function __construct(?callable $loadConfig = null)
    {
        $this->loadConfig = Closure::fromCallable($loadConfig ?? fn (string $glob) => loadConfigFromGlob($glob));
    }

    public function __invoke(): array
    {
        $config = ($this->loadConfig)(__DIR__ . '/../config/{,*.}config.php');
        return $this->applyRoutesPrefix($config);
    }

    private function applyRoutesPrefix(array $config): array
    {
        $routes = $config['routes'] ?? [];
        $healthRoute = $this->buildUnversionedHealthRouteFromExistingRoutes($routes);

        $prefixRoute = static function (array $route) {
            ['path' => $path] = $route;
            $route['path'] = sprintf('%s%s', self::ROUTES_PREFIX, $path);

            return $route;
        };
        $prefixedRoutes = map($routes, $prefixRoute);

        $config['routes'] = $healthRoute !== null ? [...$prefixedRoutes, $healthRoute] : $prefixedRoutes;

        return $config;
    }

    private function buildUnversionedHealthRouteFromExistingRoutes(array $routes): ?array
    {
        $healthRoute = first($routes, fn (array $route) => $route['path'] === '/health');
        if ($healthRoute === null) {
            return null;
        }

        $path = $healthRoute['path'];
        $healthRoute['path'] = sprintf('%s%s', self::UNVERSIONED_ROUTES_PREFIX, $path);
        $healthRoute['name'] = self::UNVERSIONED_HEALTH_ENDPOINT_NAME;

        return $healthRoute;
    }
}
