<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action;

use Doctrine\ORM\EntityManager;
use Psr\Container\ContainerInterface;
use Shlinkio\Shlink\Core\Options\AppOptions;

class HealthActionFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $em = $container->get(EntityManager::class);
        $options = $container->get(AppOptions::class);
        $logger = $container->get('Logger_Shlink');
        return new HealthAction($em->getConnection(), $options, $logger);
    }
}
