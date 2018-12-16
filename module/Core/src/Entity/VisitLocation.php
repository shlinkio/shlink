<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Entity;

use Shlinkio\Shlink\Common\Entity\AbstractEntity;
use Shlinkio\Shlink\Core\Visit\Model\VisitLocationInterface;
use function array_key_exists;

class VisitLocation extends AbstractEntity implements VisitLocationInterface
{
    /** @var string */
    private $countryCode;
    /** @var string */
    private $countryName;
    /** @var string */
    private $regionName;
    /** @var string */
    private $cityName;
    /** @var string */
    private $latitude;
    /** @var string */
    private $longitude;
    /** @var string */
    private $timezone;

    public function __construct(array $locationInfo)
    {
        $this->exchangeArray($locationInfo);
    }

    public function getCountryName(): string
    {
        return $this->countryName ?? '';
    }

    public function getLatitude(): string
    {
        return $this->latitude ?? '';
    }

    public function getLongitude(): string
    {
        return $this->longitude ?? '';
    }

    public function getCityName(): string
    {
        return $this->cityName ?? '';
    }

    /**
     * Exchange internal values from provided array
     */
    private function exchangeArray(array $array): void
    {
        if (array_key_exists('country_code', $array)) {
            $this->countryCode = (string) $array['country_code'];
        }
        if (array_key_exists('country_name', $array)) {
            $this->countryName = (string) $array['country_name'];
        }
        if (array_key_exists('region_name', $array)) {
            $this->regionName = (string) $array['region_name'];
        }
        if (array_key_exists('city', $array)) {
            $this->cityName = (string) $array['city'];
        }
        if (array_key_exists('latitude', $array)) {
            $this->latitude = (string) $array['latitude'];
        }
        if (array_key_exists('longitude', $array)) {
            $this->longitude = (string) $array['longitude'];
        }
        if (array_key_exists('time_zone', $array)) {
            $this->timezone = (string) $array['time_zone'];
        }
    }

    public function jsonSerialize(): array
    {
        return [
            'countryCode' => $this->countryCode,
            'countryName' => $this->countryName,
            'regionName' => $this->regionName,
            'cityName' => $this->cityName,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'timezone' => $this->timezone,
        ];
    }
}
