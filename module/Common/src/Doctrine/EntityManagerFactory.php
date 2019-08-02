<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Doctrine;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Persistence\Mapping\Driver\PHPDriver;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Tools\Setup;
use Psr\Container\ContainerInterface;

class EntityManagerFactory
{
    /**
     * @throws ORMException
     * @throws DBALException
     */
    public function __invoke(ContainerInterface $container): EntityManager
    {
        $globalConfig = $container->get('config');
        $isDevMode = (bool) ($globalConfig['debug'] ?? false);
        $cache = $container->has(Cache::class) ? $container->get(Cache::class) : new ArrayCache();
        $emConfig = $globalConfig['entity_manager'] ?? [];
        $connectionConfig = $emConfig['connection'] ?? [];
        $ormConfig = $emConfig['orm'] ?? [];

        $this->registerTypes($ormConfig);

        $config = Setup::createConfiguration($isDevMode, $ormConfig['proxies_dir'] ?? null, $cache);
        $config->setMetadataDriverImpl(new PHPDriver($ormConfig['entities_mappings'] ?? []));

        return EntityManager::create($connectionConfig, $config);
    }

    /**
     * @throws DBALException
     */
    private function registerTypes(array $ormConfig): void
    {
        $types = $ormConfig['types'] ?? [];

        foreach ($types as $name => $className) {
            if (! Type::hasType($name)) {
                Type::addType($name, $className);
            }
        }
    }
}
