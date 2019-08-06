<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Doctrine;

use Doctrine\DBAL\Connection;
use Psr\Container\ContainerInterface;

class NoDbNameConnectionFactory
{
    public const SERVICE_NAME = 'Shlinkio\Shlink\Common\Doctrine\NoDbNameConnection';

    public function __invoke(ContainerInterface $container): Connection
    {
        $conn = $container->get(Connection::class);
        $params = $conn->getParams();
        unset($params['dbname']);

        return new Connection($params, $conn->getDriver(), $conn->getConfiguration(), $conn->getEventManager());
    }
}
