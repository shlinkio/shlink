<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Cache;

use Doctrine\Common\Cache;
use Predis\Client as PredisClient;
use Psr\Container\ContainerInterface;

use function extension_loaded;

class CacheFactory
{
    /** @var callable|null */
    private $apcuEnabled;

    public function __construct(?callable $apcuEnabled = null)
    {
        $this->apcuEnabled = $apcuEnabled ?? function () {
            return extension_loaded('apcu');
        };
    }

    public function __invoke(ContainerInterface $container): Cache\CacheProvider
    {
        $config = $container->get('config');
        $adapter = $this->buildAdapter($config, $container);
        $adapter->setNamespace($config['cache']['namespace'] ?? '');

        return $adapter;
    }

    private function buildAdapter(array $config, ContainerInterface $container): Cache\CacheProvider
    {
        $isDebug = (bool) ($config['debug'] ?? false);
        $redisConfig = $config['cache']['redis'] ?? null;
        $apcuEnabled = ($this->apcuEnabled)();

        if ($isDebug || (! $apcuEnabled && $redisConfig === null)) {
            return new Cache\ArrayCache();
        }

        if ($redisConfig === null) {
            return new Cache\ApcuCache();
        }

        /** @var PredisClient $predis */
        $predis = $container->get(RedisFactory::SERVICE_NAME);
        return new Cache\PredisCache($predis);
    }
}
