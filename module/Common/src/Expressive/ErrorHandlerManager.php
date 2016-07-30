<?php
namespace Shlinkio\Shlink\Common\Expressive;

use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\Exception\InvalidServiceException;

class ErrorHandlerManager extends AbstractPluginManager implements ErrorHandlerManagerInterface
{
    public function validate($instance)
    {
        if (is_callable($instance)) {
            return;
        }

        throw new InvalidServiceException(sprintf(
            'Only callables are valid plugins for "%s". "%s" provided',
            __CLASS__,
            is_object($instance) ? get_class($instance) : gettype($instance)
        ));
    }
}
