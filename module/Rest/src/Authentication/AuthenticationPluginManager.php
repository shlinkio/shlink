<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Authentication;

use Zend\ServiceManager\AbstractPluginManager;

class AuthenticationPluginManager extends AbstractPluginManager implements AuthenticationPluginManagerInterface
{
    protected $instanceOf = Plugin\AuthenticationPluginInterface::class;
}
