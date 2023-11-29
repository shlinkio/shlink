<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest;

use function array_map;
use function Functional\first;
use function Shlinkio\Shlink\Config\loadConfigFromGlob;
use function sprintf;

class ConfigProvider
{
    private const ROUTES_PREFIX = '/rest/v{version:1|2|3}';
    private const UNVERSIONED_ROUTES_PREFIX = '/rest';
    public const UNVERSIONED_HEALTH_ENDPOINT_NAME = 'unversioned_health';

    public function __invoke(): array
    {
        return loadConfigFromGlob(__DIR__ . '/../config/{,*.}config.php');
    }

    public static function applyRoutesPrefix(array $routes): array
    {
        $healthRoute = self::buildUnversionedHealthRouteFromExistingRoutes($routes);
        $prefixedRoutes = array_map(static function (array $route) {
            ['path' => $path] = $route;
            $route['path'] = sprintf('%s%s', self::ROUTES_PREFIX, $path);
            return $route;
        }, $routes);

        return $healthRoute !== null ? [...$prefixedRoutes, $healthRoute] : $prefixedRoutes;
    }

    private static function buildUnversionedHealthRouteFromExistingRoutes(array $routes): ?array
    {
        $healthRoute = first($routes, fn (array $route) => $route['path'] === '/health');
        if ($healthRoute === null) {
            return null;
        }

        ['path' => $path] = $healthRoute;
        $healthRoute['path'] = sprintf('%s%s', self::UNVERSIONED_ROUTES_PREFIX, $path);
        $healthRoute['name'] = self::UNVERSIONED_HEALTH_ENDPOINT_NAME;

        return $healthRoute;
    }
}
