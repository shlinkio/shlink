<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Doctrine;

use Psr\Container\ContainerInterface;

class ReopeningEntityManagerDelegator
{
    public function __invoke(ContainerInterface $container, string $name, callable $callback): ReopeningEntityManager
    {
        return new ReopeningEntityManager($callback());
    }
}
