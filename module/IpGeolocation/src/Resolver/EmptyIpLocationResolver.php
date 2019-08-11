<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\IpGeolocation\Resolver;

use Shlinkio\Shlink\IpGeolocation\Exception\WrongIpException;
use Shlinkio\Shlink\IpGeolocation\Model;

class EmptyIpLocationResolver implements IpLocationResolverInterface
{
    /**
     * @throws WrongIpException
     */
    public function resolveIpLocation(string $ipAddress): Model\Location
    {
        return Model\Location::emptyInstance();
    }
}
