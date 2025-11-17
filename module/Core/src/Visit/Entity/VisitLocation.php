<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Entity;

use JsonSerializable;
use Shlinkio\Shlink\Common\Entity\AbstractEntity;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkVisitLocation;
use Shlinkio\Shlink\IpGeolocation\Model\Location;

class VisitLocation extends AbstractEntity implements JsonSerializable
{
    public readonly bool $isEmpty;

    private function __construct(
        public readonly string $countryCode,
        public readonly string $countryName,
        public readonly string $regionName,
        public readonly string $cityName,
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly string $timezone,
    ) {
        $this->isEmpty = (
            $countryCode === '' &&
            $countryName === '' &&
            $regionName === '' &&
            $cityName === '' &&
            $latitude === 0.0 &&
            $longitude === 0.0 &&
            $timezone === ''
        );
    }

    public static function fromLocation(Location|ImportedShlinkVisitLocation $location): self
    {
        return new self(
            countryCode: $location->countryCode,
            countryName: $location->countryName,
            regionName: $location->regionName,
            cityName: $location instanceof Location ? $location->city : $location->cityName,
            latitude: $location->latitude,
            longitude: $location->longitude,
            timezone: $location instanceof Location ? $location->timeZone : $location->timezone,
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
