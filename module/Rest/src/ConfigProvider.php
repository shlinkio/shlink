<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest;

use function Functional\first;
use function Functional\map;
use function Shlinkio\Shlink\Config\loadConfigFromGlob;
use function sprintf;
use function str_replace;

class ConfigProvider
{
    private const ROUTES_PREFIX = '/rest/v{version:1|2}';
    private const UNVERSIONED_ROUTES_PREFIX = '/rest';
    public const UNVERSIONED_HEALTH_ENDPOINT_NAME = 'unversioned_health';

    public function __invoke(): array
    {
        return loadConfigFromGlob(__DIR__ . '/../config/{,*.}config.php');
    }

    public static function applyRoutesPrefix(array $routes, bool $multiSegmentEnabled): array
    {
        $healthRoute = self::buildUnversionedHealthRouteFromExistingRoutes($routes);
        $prefixedRoutes = map($routes, static function (array $route) use ($multiSegmentEnabled) {
            ['path' => $path] = $route;
            if ($multiSegmentEnabled) {
                $path = str_replace('{shortCode}', '{shortCode:.+}', $path);
            }
            $route['path'] = sprintf('%s%s', self::ROUTES_PREFIX, $path);

            return $route;
        });

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
