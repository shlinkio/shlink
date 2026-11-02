<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Geolocation;

use Laminas\Diactoros\ServerRequestFactory;
use Shlinkio\Shlink\Common\Util\IpAddress;
use Shlinkio\Shlink\Core\Exception\IpCannotBeLocatedException;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Geolocation\Exception\LocationResolutionException;
use Shlinkio\Shlink\Geolocation\Model\Location;
use Shlinkio\Shlink\Geolocation\Resolver\LocationResolverInterface;

use const Shlinkio\Shlink\Geolocation\IP_ADDRESS_REQUEST_ATTRIBUTE;

readonly class VisitToLocationHelper implements VisitToLocationHelperInterface
{
    public function __construct(private LocationResolverInterface $locationResolver) {}

    /**
     * @throws IpCannotBeLocatedException
     */
    public function resolveVisitLocation(Visit $visit): Location
    {
        if (!$visit->hasRemoteAddr()) {
            throw IpCannotBeLocatedException::forEmptyAddress();
        }

        $ipAddr = $visit->remoteAddr ?? '';
        if ($ipAddr === IpAddress::LOCALHOST) {
            throw IpCannotBeLocatedException::forLocalhost();
        }

        try {
            return $this->locationResolver->resolveRequestLocation(ServerRequestFactory::fromGlobals()->withAttribute(
                IP_ADDRESS_REQUEST_ATTRIBUTE,
                $ipAddr,
            ));
        } catch (LocationResolutionException $e) {
            throw IpCannotBeLocatedException::forError($e);
        }
    }
}
