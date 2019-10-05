<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Entity;

use Shlinkio\Shlink\Common\Entity\AbstractEntity;
use Shlinkio\Shlink\Core\Visit\Model\VisitLocationInterface;
use Shlinkio\Shlink\IpGeolocation\Model\Location;

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

    public function __construct(Location $location)
    {
        $this->exchangeLocationInfo($location);
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

    private function exchangeLocationInfo(Location $info): void
    {
        $this->countryCode = $info->countryCode();
        $this->countryName = $info->countryName();
        $this->regionName = $info->regionName();
        $this->cityName = $info->city();
        $this->latitude = (string) $info->latitude();
        $this->longitude = (string) $info->longitude();
        $this->timezone = $info->timeZone();
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

    public function isEmpty(): bool
    {
        return
            $this->countryCode === '' &&
            $this->countryName === '' &&
            $this->regionName === '' &&
            $this->cityName === '' &&
            ((float) $this->latitude) === 0.0 &&
            ((float) $this->longitude) === 0.0 &&
            $this->timezone === '';
    }
}
