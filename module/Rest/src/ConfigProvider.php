<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest;

use Zend\Config\Factory;
use Zend\Stdlib\Glob;
use function sprintf;

class ConfigProvider
{
    private const ROUTES_PREFIX = '/rest';
    private const ROUTES_VERSION_PARAM = '/v{version:1}';

    public function __invoke()
    {
        /** @var array $config */
        $config = Factory::fromFiles(Glob::glob(__DIR__ . '/../config/{,*.}config.php', Glob::GLOB_BRACE));
        return $this->applyRoutesPrefix($config);
    }

    private function applyRoutesPrefix(array $config): array
    {
        $routes =& $config['routes'] ?? [];

        // Prepend the routes prefix to every path
        foreach ($routes as $key => $route) {
            ['can_be_versioned' => $routeCanBeVersioned, 'path' => $path] = $route;
            $routes[$key]['path'] = sprintf(
                '%s%s%s',
                self::ROUTES_PREFIX,
                $routeCanBeVersioned ? self::ROUTES_VERSION_PARAM : '',
                $path
            );
            unset($routes[$key]['can_be_versioned']);
        }

        return $config;
    }
}
