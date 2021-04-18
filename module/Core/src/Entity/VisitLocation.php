<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Entity;

use Shlinkio\Shlink\Common\Entity\AbstractEntity;
use Shlinkio\Shlink\Core\Visit\Model\VisitLocationInterface;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkVisitLocation;
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

    private function __construct()
    {
    }

    public static function fromGeolocation(Location $location): self
    {
        $instance = new self();

        $instance->countryCode = $location->countryCode();
        $instance->countryName = $location->countryName();
        $instance->regionName = $location->regionName();
        $instance->cityName = $location->city();
        $instance->latitude = $location->latitude();
        $instance->longitude = $location->longitude();
        $instance->timezone = $location->timeZone();
        $instance->computeIsEmpty();

        return $instance;
    }

    public static function fromImport(ImportedShlinkVisitLocation $location): self
    {
        $instance = new self();

        $instance->countryCode = $location->countryCode();
        $instance->countryName = $location->countryName();
        $instance->regionName = $location->regionName();
        $instance->cityName = $location->cityName();
        $instance->latitude = $location->latitude();
        $instance->longitude = $location->longitude();
        $instance->timezone = $location->timeZone();
        $instance->computeIsEmpty();

        return $instance;
    }

    private function computeIsEmpty(): void
    {
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
