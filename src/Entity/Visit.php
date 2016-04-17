<?php
namespace Acelaya\UrlShortener\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Visit
 * @author
 * @link
 *
 * @ORM\Entity
 * @ORM\Table(name="visits")
 */
class Visit extends AbstractEntity
{
    /**
     * @var string
     * @ORM\Column(type="string", length=256)
     */
    protected $referer;
    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $date;
    /**
     * @var string
     * @ORM\Column(type="string", length=256)
     */
    protected $country;
    /**
     * @var string
     * @ORM\Column(type="string", length=256)
     */
    protected $platform;
    /**
     * @var string
     * @ORM\Column(type="string", length=256)
     */
    protected $browser;
    /**
     * @var ShortUrl
     * @ORM\ManyToOne(targetEntity=ShortUrl::class)
     * @ORM\JoinColumn(name="short_url_id", referencedColumnName="id")
     */
    protected $shortUrl;

    /**
     * @return string
     */
    public function getReferer()
    {
        return $this->referer;
    }

    /**
     * @param string $referer
     * @return $this
     */
    public function setReferer($referer)
    {
        $this->referer = $referer;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param \DateTime $date
     * @return $this
     */
    public function setDate($date)
    {
        $this->date = $date;
        return $this;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param string $country
     * @return $this
     */
    public function setCountry($country)
    {
        $this->country = $country;
        return $this;
    }

    /**
     * @return string
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * @param string $platform
     * @return $this
     */
    public function setPlatform($platform)
    {
        $this->platform = $platform;
        return $this;
    }

    /**
     * @return string
     */
    public function getBrowser()
    {
        return $this->browser;
    }

    /**
     * @param string $browser
     * @return $this
     */
    public function setBrowser($browser)
    {
        $this->browser = $browser;
        return $this;
    }

    /**
     * @return ShortUrl
     */
    public function getShortUrl()
    {
        return $this->shortUrl;
    }

    /**
     * @param ShortUrl $shortUrl
     * @return $this
     */
    public function setShortUrl($shortUrl)
    {
        $this->shortUrl = $shortUrl;
        return $this;
    }
}
