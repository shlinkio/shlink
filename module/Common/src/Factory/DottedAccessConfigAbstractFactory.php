<?php
namespace Shlinkio\Shlink\Common\Factory;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Shlinkio\Shlink\Common\Exception\InvalidArgumentException;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;

class DottedAccessConfigAbstractFactory implements AbstractFactoryInterface
{
    /**
     * Can the factory create an instance for the service?
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @return bool
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        return substr_count($requestedName, '.') > 0;
    }

    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     * @throws InvalidArgumentException
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $parts = explode('.', $requestedName);
        $serviceName = array_shift($parts);
        if (! $container->has($serviceName)) {
            throw new ServiceNotCreatedException(sprintf(
                'Defined service "%s" could not be found in container after resolving dotted expression "%s".',
                $serviceName,
                $requestedName
            ));
        }

        $array = $container->get($serviceName);
        return $this->readKeysFromArray($parts, $array);
    }

    /**
     * @param array $keys
     * @param array|\ArrayAccess $array
     * @return mixed|null
     * @throws  InvalidArgumentException
     */
    private function readKeysFromArray(array $keys, $array)
    {
        $key = array_shift($keys);

        // When one of the provided keys is not found, throw an exception
        if (! isset($array[$key])) {
            throw new InvalidArgumentException(sprintf(
                'The key "%s" provided in the dotted notation could not be found in the array service',
                $key
            ));
        }

        $value = $array[$key];
        if (! empty($keys) && (is_array($value) || $value instanceof \ArrayAccess)) {
            $value = $this->readKeysFromArray($keys, $value);
        }

        return $value;
    }
}
