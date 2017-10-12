<?php
declare(strict_types=1);

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
        $emConfig = isset($globalConfig['entity_manager']) ? $globalConfig['entity_manager'] : [];
        $connecitonConfig = isset($emConfig['connection']) ? $emConfig['connection'] : [];
        $ormConfig = isset($emConfig['orm']) ? $emConfig['orm'] : [];

        return EntityManager::create($connecitonConfig, Setup::createAnnotationMetadataConfiguration(
            isset($ormConfig['entities_paths']) ? $ormConfig['entities_paths'] : [],
            $isDevMode,
            isset($ormConfig['proxies_dir']) ? $ormConfig['proxies_dir'] : null,
            $cache,
            false
        ));
    }
}
