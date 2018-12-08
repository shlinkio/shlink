<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Model;

final class UnknownVisitLocation implements VisitLocationInterface
{
    public function getCountryName(): string
    {
        return 'Unknown';
    }

    public function getLatitude(): string
    {
        return '0.0';
    }

    public function getLongitude(): string
    {
        return '0.0';
    }

    public function getCityName(): string
    {
        return 'Unknown';
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return [
            'countryCode' => 'Unknown',
            'countryName' => 'Unknown',
            'regionName' => 'Unknown',
            'cityName' => 'Unknown',
            'latitude' => '0.0',
            'longitude' => '0.0',
            'timezone' => 'Unknown',
        ];
    }
}
