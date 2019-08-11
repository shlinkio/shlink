<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Middleware;

use Psr\Container\ContainerInterface;
use RKA\Middleware\IpAddress;

class IpAddressMiddlewareFactory
{
    public const REQUEST_ATTR = 'remote_address';

    public function __invoke(ContainerInterface $container): IpAddress
    {
        $config = $container->get('config');
        $headersToInspect = $config['ip_address_resolution']['headers_to_inspect'] ?? [];
        return new IpAddress(true, [], self::REQUEST_ATTR, $headersToInspect);
    }
}
