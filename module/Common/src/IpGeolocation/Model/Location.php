<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\IpGeolocation\Model;

final class Location
{
    /** @var string */
    private $countryCode;
    /** @var string */
    private $countryName;
    /** @var string */
    private $regionName;
    /** @var string */
    private $city;
    /** @var float */
    private $latitude;
    /** @var float */
    private $longitude;
    /** @var string */
    private $timeZone;

    public function __construct(
        string $countryCode,
        string $countryName,
        string $regionName,
        string $city,
        float $latitude,
        float $longitude,
        string $timeZone
    ) {
        $this->countryCode = $countryCode;
        $this->countryName = $countryName;
        $this->regionName = $regionName;
        $this->city = $city;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->timeZone = $timeZone;
    }

    public static function emptyInstance(): self
    {
        return new self('', '', '', '', 0.0, 0.0, '');
    }

    public function countryCode(): string
    {
        return $this->countryCode;
    }

    public function countryName(): string
    {
        return $this->countryName;
    }

    public function regionName(): string
    {
        return $this->regionName;
    }

    public function city(): string
    {
        return $this->city;
    }

    public function latitude(): float
    {
        return $this->latitude;
    }

    public function longitude(): float
    {
        return $this->longitude;
    }

    public function timeZone(): string
    {
        return $this->timeZone;
    }
}
