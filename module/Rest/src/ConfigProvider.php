<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest;

use Zend\Config\Factory;
use Zend\Stdlib\Glob;
use function sprintf;

class ConfigProvider
{
    private const ROUTES_PREFIX = '/rest/v{version:1}';

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
            ['path' => $path] = $route;
            $routes[$key]['path'] = sprintf('%s%s', self::ROUTES_PREFIX, $path);
        }

        return $config;
    }
}
