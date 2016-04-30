<?php
namespace Acelaya\UrlShortener\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class ShortUrl
 * @author
 * @link
 *
 * @ORM\Entity
 * @ORM\Table(name="short_urls")
 */
class ShortUrl extends AbstractEntity
{
    /**
     * @var string
     * @ORM\Column(name="original_url", type="string", nullable=false, length=1024, unique=true)
     */
    protected $originalUrl;
    /**
     * @var string
     * @ORM\Column(name="short_code", type="string", nullable=false, length=10, unique=true)
     */
    protected $shortCode;
    /**
     * @var \DateTime
     * @ORM\Column(name="date_created", type="datetime")
     */
    protected $dateCreated;
    /**
     * @var Collection|Visit[]
     * @ORM\OneToMany(targetEntity=Visit::class, mappedBy="shortUrl", fetch="EXTRA_LAZY")
     */
    protected $visits;

    /**
     * ShortUrl constructor.
     */
    public function __construct()
    {
        $this->setDateCreated(new \DateTime());
        $this->setVisits(new ArrayCollection());
        $this->setShortCode('');
    }

    /**
     * @return string
     */
    public function getOriginalUrl()
    {
        return $this->originalUrl;
    }

    /**
     * @param string $originalUrl
     * @return $this
     */
    public function setOriginalUrl($originalUrl)
    {
        $this->originalUrl = (string) $originalUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getShortCode()
    {
        return $this->shortCode;
    }

    /**
     * @param string $shortCode
     * @return $this
     */
    public function setShortCode($shortCode)
    {
        $this->shortCode = $shortCode;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDateCreated()
    {
        return $this->dateCreated;
    }

    /**
     * @param \DateTime $dateCreated
     * @return $this
     */
    public function setDateCreated($dateCreated)
    {
        $this->dateCreated = $dateCreated;
        return $this;
    }

    /**
     * @return Visit[]|Collection
     */
    public function getVisits()
    {
        return $this->visits;
    }

    /**
     * @param Visit[]|Collection $visits
     * @return $this
     */
    public function setVisits($visits)
    {
        $this->visits = $visits;
        return $this;
    }
}
