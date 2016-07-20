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
     * @ORM\Column()
     */
    protected $countryCode;
    /**
     * @var string
     * @ORM\Column()
     */
    protected $countryName;
    /**
     * @var string
     * @ORM\Column()
     */
    protected $regionName;
    /**
     * @var string
     * @ORM\Column()
     */
    protected $cityName;
    /**
     * @var string
     * @ORM\Column()
     */
    protected $latitude;
    /**
     * @var string
     * @ORM\Column()
     */
    protected $longitude;
    /**
     * @var string
     * @ORM\Column()
     */
    protected $areaCode;
    /**
     * @var string
     * @ORM\Column()
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
    public function getAreaCode()
    {
        return $this->areaCode;
    }

    /**
     * @param string $areaCode
     * @return $this
     */
    public function setAreaCode($areaCode)
    {
        $this->areaCode = $areaCode;
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
        if (array_key_exists('countryCode', $array)) {
            $this->setCountryCode($array['countryCode']);
        }
        if (array_key_exists('countryName', $array)) {
            $this->setCountryName($array['countryName']);
        }
        if (array_key_exists('regionName', $array)) {
            $this->setRegionName($array['regionName']);
        }
        if (array_key_exists('cityName', $array)) {
            $this->setCityName($array['cityName']);
        }
        if (array_key_exists('latitude', $array)) {
            $this->setLatitude($array['latitude']);
        }
        if (array_key_exists('longitude', $array)) {
            $this->setLongitude($array['longitude']);
        }
        if (array_key_exists('areaCode', $array)) {
            $this->setAreaCode($array['areaCode']);
        }
        if (array_key_exists('timezone', $array)) {
            $this->setTimezone($array['timezone']);
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
            'areaCode' => $this->areaCode,
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
