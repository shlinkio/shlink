<?php
namespace Shlinkio\Shlink\Core\Entity;

use Shlinkio\Shlink\Common\Entity\AbstractEntity;
use Doctrine\ORM\Mapping as ORM;
use Zend\Stdlib\ArraySerializableInterface;

/**
 * Class VisitLocation
 * @author
 * @link
 *
 * @ORM\Entity()
 * @ORM\Table(name="visit_locations")
 */
class VisitLocation extends AbstractEntity implements ArraySerializableInterface, \JsonSerializable
{
    /**
     * @var string
     * @ORM\Column(nullable=true)
     */
    protected $countryCode;
    /**
     * @var string
     * @ORM\Column(nullable=true)
     */
    protected $countryName;
    /**
     * @var string
     * @ORM\Column(nullable=true)
     */
    protected $regionName;
    /**
     * @var string
     * @ORM\Column(nullable=true)
     */
    protected $cityName;
    /**
     * @var string
     * @ORM\Column(nullable=true)
     */
    protected $latitude;
    /**
     * @var string
     * @ORM\Column(nullable=true)
     */
    protected $longitude;
    /**
     * @var string
     * @ORM\Column(nullable=true)
     */
    protected $timezone;

    /**
     * @return string
     */
    public function getCountryCode()
    {
        return $this->countryCode;
    }

    /**
     * @param string $countryCode
     * @return $this
     */
    public function setCountryCode($countryCode)
    {
        $this->countryCode = $countryCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getCountryName()
    {
        return $this->countryName;
    }

    /**
     * @param string $countryName
     * @return $this
     */
    public function setCountryName($countryName)
    {
        $this->countryName = $countryName;
        return $this;
    }

    /**
     * @return string
     */
    public function getRegionName()
    {
        return $this->regionName;
    }

    /**
     * @param string $regionName
     * @return $this
     */
    public function setRegionName($regionName)
    {
        $this->regionName = $regionName;
        return $this;
    }

    /**
     * @return string
     */
    public function getCityName()
    {
        return $this->cityName;
    }

    /**
     * @param string $cityName
     * @return $this
     */
    public function setCityName($cityName)
    {
        $this->cityName = $cityName;
        return $this;
    }

    /**
     * @return string
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * @param string $latitude
     * @return $this
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;
        return $this;
    }

    /**
     * @return string
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * @param string $longitude
     * @return $this
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;
        return $this;
    }

    /**
     * @return string
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    /**
     * @param string $timezone
     * @return $this
     */
    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;
        return $this;
    }

    /**
     * Exchange internal values from provided array
     *
     * @param  array $array
     * @return void
     */
    public function exchangeArray(array $array)
    {
        if (array_key_exists('country_code', $array)) {
            $this->setCountryCode($array['country_code']);
        }
        if (array_key_exists('country_name', $array)) {
            $this->setCountryName($array['country_name']);
        }
        if (array_key_exists('region_name', $array)) {
            $this->setRegionName($array['region_name']);
        }
        if (array_key_exists('city', $array)) {
            $this->setCityName($array['city']);
        }
        if (array_key_exists('latitude', $array)) {
            $this->setLatitude($array['latitude']);
        }
        if (array_key_exists('longitude', $array)) {
            $this->setLongitude($array['longitude']);
        }
        if (array_key_exists('time_zone', $array)) {
            $this->setTimezone($array['time_zone']);
        }
    }

    /**
     * Return an array representation of the object
     *
     * @return array
     */
    public function getArrayCopy()
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

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return $this->getArrayCopy();
    }
}
