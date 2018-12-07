<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Factory;

use Doctrine\Common\Cache;
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Memcached;
use Shlinkio\Shlink\Core\Options\AppOptions;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;
use function Functional\contains;
use function Shlinkio\Shlink\Common\env;
use function sys_get_temp_dir;

class CacheFactory implements FactoryInterface
{
    private const VALID_CACHE_ADAPTERS = [
        Cache\ApcuCache::class,
        Cache\ArrayCache::class,
        Cache\FilesystemCache::class,
        Cache\PhpFileCache::class,
        Cache\MemcachedCache::class,
    ];
    private const DEFAULT_MEMCACHED_PORT = 11211;

    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $appOptions = $container->get(AppOptions::class);
        $adapter = $this->getAdapter($container);
        $adapter->setNamespace((string) $appOptions);

        return $adapter;
    }

    private function getAdapter(ContainerInterface $container): Cache\CacheProvider
    {
        // Try to get the adapter from config
        $config = $container->get('config');
        if (isset($config['cache']['adapter']) && contains(self::VALID_CACHE_ADAPTERS, $config['cache']['adapter'])) {
            return $this->resolveCacheAdapter($config['cache']);
        }

        // If the adapter has not been set in config, create one based on environment
        return env('APP_ENV', 'pro') === 'pro' ? new Cache\ApcuCache() : new Cache\ArrayCache();
    }

    private function resolveCacheAdapter(array $cacheConfig): Cache\CacheProvider
    {
        switch ($cacheConfig['adapter']) {
            case Cache\ArrayCache::class:
            case Cache\ApcuCache::class:
                return new $cacheConfig['adapter']();
            case Cache\FilesystemCache::class:
            case Cache\PhpFileCache::class:
                return new $cacheConfig['adapter']($cacheConfig['options']['dir'] ?? sys_get_temp_dir());
            case Cache\MemcachedCache::class:
                $cache = new Cache\MemcachedCache();
                $cache->setMemcached($this->buildMemcached($cacheConfig));
                return $cache;
            default:
                return new Cache\ArrayCache();
        }
    }

    private function buildMemcached(array $cacheConfig): Memcached
    {
        $memcached = new Memcached();
        $servers = $cacheConfig['options']['servers'] ?? [];

        foreach ($servers as $server) {
            $this->addMemcachedServer($memcached, $server);
        }

        return $memcached;
    }

    private function addMemcachedServer(Memcached $memcached, array $server): void
    {
        if (! isset($server['host'])) {
            return;
        }
        $port = (int) ($server['port'] ?? self::DEFAULT_MEMCACHED_PORT);

        $memcached->addServer($server['host'], $port);
    }
}
