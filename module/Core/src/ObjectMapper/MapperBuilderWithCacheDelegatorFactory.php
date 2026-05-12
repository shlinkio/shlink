<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ObjectMapper;

use CuyZ\Valinor\Cache\FileSystemCache;
use CuyZ\Valinor\Cache\FileWatchingCache;
use CuyZ\Valinor\MapperBuilder;
use Psr\Container\ContainerInterface;
use Shlinkio\Shlink\Core\Config\EnvVars;

/**
 * Decorates a MapperBuilder with cache based on EnvVars::isProdEnv()
 */
class MapperBuilderWithCacheDelegatorFactory
{
    public function __invoke(ContainerInterface $container, string $name, callable $callback): MapperBuilder
    {
        /** @var MapperBuilder $mapperBuilder */
        $mapperBuilder = $callback();

        $cache = new FileSystemCache('data/cache/valinor');
        if (! EnvVars::isProdEnv()) {
            $cache = new FileWatchingCache($cache);
        }

        return $mapperBuilder->withCache($cache);
    }
}
