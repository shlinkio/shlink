<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\IpGeolocation;

use Shlinkio\Shlink\Common\Exception\WrongIpException;

interface IpLocationResolverInterface
{
    /**
     * @throws WrongIpException
     */
    public function resolveIpLocation(string $ipAddress): array;
}
