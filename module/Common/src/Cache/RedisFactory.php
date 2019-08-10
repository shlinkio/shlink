<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Cache;

use Predis\Client as PredisClient;
use Psr\Container\ContainerInterface;

use function count;
use function explode;
use function is_string;

class RedisFactory
{
    public const SERVICE_NAME = 'Shlinkio\Shlink\Common\Cache\Redis';

    public function __invoke(ContainerInterface $container): PredisClient
    {
        $config = $container->get('config');
        $redisConfig = $config['cache']['redis'] ?? $config['redis'] ?? [];

        $servers = $redisConfig['servers'] ?? [];
        $servers = is_string($servers) ? explode(',', $servers) : $servers;
        $options = count($servers) <= 1 ? null : ['cluster' => 'redis'];

        return new PredisClient($servers, $options);
    }
}
