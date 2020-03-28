<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Entity;

use Shlinkio\Shlink\Common\Entity\AbstractEntity;
use Shlinkio\Shlink\Core\Visit\Model\VisitLocationInterface;
use Shlinkio\Shlink\IpGeolocation\Model\Location;

class VisitLocation extends AbstractEntity implements VisitLocationInterface
{
    private string $countryCode;
    private string $countryName;
    private string $regionName;
    private string $cityName;
    private float $latitude;
    private float $longitude;
    private string $timezone;
    private bool $isEmpty;

    public function __construct(Location $location)
    {
        $this->exchangeLocationInfo($location);
    }

    public function getCountryName(): string
    {
        return $this->countryName;
    }

    public function getLatitude(): float
    {
        return $this->latitude;
    }

    public function getLongitude(): float
    {
        return $this->longitude;
    }

    public function getCityName(): string
    {
        return $this->cityName;
    }

    public function isEmpty(): bool
    {
        return $this->isEmpty;
    }

    private function exchangeLocationInfo(Location $info): void
    {
        $this->countryCode = $info->countryCode();
        $this->countryName = $info->countryName();
        $this->regionName = $info->regionName();
        $this->cityName = $info->city();
        $this->latitude = $info->latitude();
        $this->longitude = $info->longitude();
        $this->timezone = $info->timeZone();
        $this->isEmpty = (
            $this->countryCode === '' &&
            $this->countryName === '' &&
            $this->regionName === '' &&
            $this->cityName === '' &&
            $this->latitude === 0.0 &&
            $this->longitude === 0.0 &&
            $this->timezone === ''
        );
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
            'isEmpty' => $this->isEmpty,
        ];
    }
}
