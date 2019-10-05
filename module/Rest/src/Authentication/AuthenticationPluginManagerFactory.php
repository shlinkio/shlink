<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Authentication;

use Psr\Container\ContainerInterface;

class AuthenticationPluginManagerFactory
{
    public function __invoke(ContainerInterface $container): AuthenticationPluginManager
    {
        $config = $container->has('config') ? $container->get('config') : [];
        return new AuthenticationPluginManager($container, $config['auth']['plugins'] ?? []);
    }
}
