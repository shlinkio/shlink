<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Model;

final class UnknownVisitLocation implements VisitLocationInterface
{
    public function getCountryName(): string
    {
        return 'Unknown';
    }

    public function getLatitude(): float
    {
        return 0.0;
    }

    public function getLongitude(): float
    {
        return 0.0;
    }

    public function getCityName(): string
    {
        return 'Unknown';
    }

    public function jsonSerialize(): array
    {
        return [
            'countryCode' => 'Unknown',
            'countryName' => 'Unknown',
            'regionName' => 'Unknown',
            'cityName' => 'Unknown',
            'latitude' => 0.0,
            'longitude' => 0.0,
            'timezone' => 'Unknown',
        ];
    }
}
