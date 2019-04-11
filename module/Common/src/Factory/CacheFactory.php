<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Factory;

use Doctrine\Common\Cache;
use Interop\Container\ContainerInterface;
use Shlinkio\Shlink\Core\Options\AppOptions;
use Zend\ServiceManager\Factory\FactoryInterface;

use function Shlinkio\Shlink\Common\env;

class CacheFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): Cache\Cache
    {
        $appOptions = $container->get(AppOptions::class);
        $adapter = env('APP_ENV', 'pro') === 'pro' ? new Cache\ApcuCache() : new Cache\ArrayCache();
        $adapter->setNamespace((string) $appOptions);

        return $adapter;
    }
}
