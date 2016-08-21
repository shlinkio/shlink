<?php
namespace Shlinkio\Shlink\Common\Factory;

use Doctrine\Common\Cache;
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Shlinkio\Shlink\Core\Options\AppOptions;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;

class CacheFactory implements FactoryInterface
{
    const VALID_CACHE_ADAPTERS = [
        Cache\ApcuCache::class,
        Cache\ArrayCache::class,
        Cache\FilesystemCache::class,
        Cache\PhpFileCache::class,
        Cache\MemcachedCache::class,
    ];

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
        $adapter->setNamespace($appOptions->__toString());

        return $adapter;
    }

    /**
     * @param ContainerInterface $container
     * @return Cache\CacheProvider
     */
    protected function getAdapter(ContainerInterface $container)
    {
        // Try to get the adapter from config
        $config = $container->get('config');
        if (isset($config['cache'])
            && isset($config['cache']['adapter'])
            && in_array($config['cache']['adapter'], self::VALID_CACHE_ADAPTERS)
        ) {
            return $this->resolveCacheAdapter($config['cache']);
        }

        // If the adapter has not been set in config, create one based on environment
        return env('APP_ENV', 'pro') === 'pro' ? new Cache\ApcuCache() : new Cache\ArrayCache();
    }

    /**
     * @param array $cacheConfig
     * @return Cache\CacheProvider
     */
    protected function resolveCacheAdapter(array $cacheConfig)
    {
        switch ($cacheConfig['adapter']) {
            case Cache\ArrayCache::class:
            case Cache\ApcuCache::class:
                return new $cacheConfig['adapter']();
            case Cache\FilesystemCache::class:
            case Cache\PhpFileCache::class:
                return new $cacheConfig['adapter']($cacheConfig['options']['dir']);
            case Cache\MemcachedCache::class:
                $memcached = new \Memcached();
                $servers = isset($cacheConfig['options']['servers']) ? $cacheConfig['options']['servers'] : [];

                foreach ($servers as $server) {
                    if (! isset($server['host'])) {
                        continue;
                    }
                    $port = isset($server['port']) ? intval($server['port']) : 11211;

                    $memcached->addServer($server['host'], $port);
                }

                $cache = new Cache\MemcachedCache();
                $cache->setMemcached($memcached);
                return $cache;
            default:
                return new Cache\ArrayCache();
        }
    }
}
