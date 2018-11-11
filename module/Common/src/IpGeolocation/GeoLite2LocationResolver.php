<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\IpGeolocation;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use GeoIp2\Model\City;
use MaxMind\Db\Reader\InvalidDatabaseException;
use Shlinkio\Shlink\Common\Exception\WrongIpException;

class GeoLite2LocationResolver implements IpLocationResolverInterface
{
    /**
     * @var Reader
     */
    private $geoLiteDbReader;

    public function __construct(Reader $geoLiteDbReader)
    {
        $this->geoLiteDbReader = $geoLiteDbReader;
    }

    /**
     * @param string $ipAddress
     * @return array
     * @throws WrongIpException
     */
    public function resolveIpLocation(string $ipAddress): array
    {
        try {
            $city = $this->geoLiteDbReader->city($ipAddress);
            return $this->mapFields($city);
        } catch (AddressNotFoundException $e) {
            throw WrongIpException::fromIpAddress($ipAddress, $e);
        } catch (InvalidDatabaseException $e) {
            throw new WrongIpException('Provided GeoLite2 db file is invalid', 0, $e);
        }
    }

    private function mapFields(City $city): array
    {
        return [
            'country_code' => $city->country->isoCode ?? '',
            'country_name' => $city->country->name ?? '',
            'region_name' => $city->mostSpecificSubdivision->name ?? '',
            'city' => $city->city->name ?? '',
            'latitude' => (string) $city->location->latitude, // FIXME Cast to string for BC compatibility
            'longitude' => (string) $city->location->longitude, // FIXME Cast to string for BC compatibility
            'time_zone' => $city->location->timeZone ?? '',
        ];
    }
}
