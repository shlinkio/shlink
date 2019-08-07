<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Cache;

use Predis\Client as PredisClient;
use Psr\Container\ContainerInterface;

use function array_shift;
use function count;
use function is_array;
use function is_string;

class RedisFactory
{
    public const SERVICE_NAME = 'Shlinkio\Shlink\Common\Cache\Redis';

    public function __invoke(ContainerInterface $container): PredisClient
    {
        $redisConfig = $container->get('config')['redis'] ?? [];
        $servers = $redisConfig['servers'] ?? [];

        if (is_array($servers) && count($servers) === 1) {
            $servers = array_shift($servers);
        }

        $options = is_string($servers) || count($servers) < 1 ? null : ['cluster' => 'redis'];
        return new PredisClient($servers, $options);
    }
}
