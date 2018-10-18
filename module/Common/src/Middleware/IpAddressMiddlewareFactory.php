<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Middleware;

use Interop\Container\ContainerInterface;
use RKA\Middleware\IpAddress;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;

class IpAddressMiddlewareFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when creating a service.
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): IpAddress
    {
        return new IpAddress(true, []);
    }
}
