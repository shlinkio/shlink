<?php
namespace Shlinkio\Shlink\Common\Factory;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;

class EntityManagerFactory implements FactoryInterface
{
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
        $globalConfig = $container->get('config');
        $isDevMode = isset($globalConfig['debug']) ? ((bool) $globalConfig['debug']) : false;
        $cache = $container->has(Cache::class) ? $container->get(Cache::class) : new ArrayCache();
        $dbConfig = isset($globalConfig['database']) ? $globalConfig['database'] : [];

        return EntityManager::create($dbConfig, Setup::createAnnotationMetadataConfiguration(
            ['module/Core/src/Entity'],
            $isDevMode,
            'data/proxies',
            $cache,
            false
        ));
    }
}
