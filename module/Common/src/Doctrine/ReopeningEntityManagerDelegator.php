<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;

class ReopeningEntityManagerDelegator
{
    public function __invoke(ContainerInterface $container, string $name, callable $callback): ReopeningEntityManager
    {
        /** @var EntityManagerInterface $em */
        $em = $callback();
        return new ReopeningEntityManager($em);
    }
}
