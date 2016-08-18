<?php
namespace Shlinkio\Shlink\Common\Image;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use mikehaertl\wkhtmlto\Image;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;

class ImageFactory implements FactoryInterface
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
        $config = $container->get('config')['phpwkhtmltopdf'];
        return new Image(isset($config['images']) ? $config['images'] : null);
    }
}
