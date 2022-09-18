<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit;

use Shlinkio\Shlink\Common\Util\IpAddress;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Exception\IpCannotBeLocatedException;
use Shlinkio\Shlink\IpGeolocation\Exception\WrongIpException;
use Shlinkio\Shlink\IpGeolocation\Model\Location;
use Shlinkio\Shlink\IpGeolocation\Resolver\IpLocationResolverInterface;

class VisitToLocationHelper implements VisitToLocationHelperInterface
{
    public function __construct(private readonly IpLocationResolverInterface $ipLocationResolver)
    {
    }

    /**
     * @throws IpCannotBeLocatedException
     */
    public function resolveVisitLocation(Visit $visit): Location
    {
        if (! $visit->hasRemoteAddr()) {
            throw IpCannotBeLocatedException::forEmptyAddress();
        }

        $ipAddr = $visit->getRemoteAddr() ?? '';
        if ($ipAddr === IpAddress::LOCALHOST) {
            throw IpCannotBeLocatedException::forLocalhost();
        }

        try {
            return $this->ipLocationResolver->resolveIpLocation($ipAddr);
        } catch (WrongIpException $e) {
            throw IpCannotBeLocatedException::forError($e);
        }
    }
}
