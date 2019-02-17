<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\IpGeolocation;

use Shlinkio\Shlink\Common\Exception\WrongIpException;

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
