<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\IpGeolocation\Resolver;

use Shlinkio\Shlink\Common\Exception\WrongIpException;
use Shlinkio\Shlink\IpGeolocation\Model;

interface IpLocationResolverInterface
{
    /**
     * @throws WrongIpException
     */
    public function resolveIpLocation(string $ipAddress): Model\Location;
}
