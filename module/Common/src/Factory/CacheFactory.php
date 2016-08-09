<?php
namespace Shlinkio\Shlink\Common\Factory;

use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\ArrayCache;
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;

class CacheFactory implements FactoryInterface
{
    const VALID_CACHE_ADAPTERS = [
        ApcuCache::class,
        ArrayCache::class,
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
        // Try to get the adapter from config
        $config = $container->get('config');
        if (isset($config['cache'])
            && isset($config['cache']['adapter'])
            && in_array($config['cache']['adapter'], self::VALID_CACHE_ADAPTERS)
        ) {
            return new $config['cache']['adapter']();
        }

        // If the adapter has not been set in config, create one based on environment
        return env('APP_ENV', 'pro') === 'pro' ? new ApcuCache() : new ArrayCache();
    }
}
