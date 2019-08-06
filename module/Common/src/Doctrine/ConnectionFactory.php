<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Psr\Container\ContainerInterface;

class ConnectionFactory
{
    public function __invoke(ContainerInterface $container): Connection
    {
        $em = $container->get(EntityManager::class);
        return $em->getConnection();
    }
}
