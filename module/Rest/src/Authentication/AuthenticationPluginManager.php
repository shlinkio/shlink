<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Authentication;

use Laminas\ServiceManager\AbstractPluginManager;

class AuthenticationPluginManager extends AbstractPluginManager implements AuthenticationPluginManagerInterface
{
    protected $instanceOf = Plugin\AuthenticationPluginInterface::class; // phpcs:ignore
}
