<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Entity;

use Doctrine\ORM\Mapping as ORM;
use Shlinkio\Shlink\Common\Entity\AbstractEntity;

/**
 * Class Visit
 * @author
 * @link
 *
 * @ORM\Entity(repositoryClass="Shlinkio\Shlink\Core\Repository\VisitRepository")
 * @ORM\Table(name="visits")
 */
class Visit extends AbstractEntity implements \JsonSerializable
{
    /**
     * @var string
     * @ORM\Column(type="string", length=256, nullable=true)
     */
    protected $referer;
    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $date;
    /**
     * @var string
     * @ORM\Column(type="string", length=256, name="remote_addr", nullable=true)
     */
    protected $remoteAddr;
    /**
     * @var string
     * @ORM\Column(type="string", length=256, name="user_agent", nullable=true)
     */
    protected $userAgent;
    /**
     * @var ShortUrl
     * @ORM\ManyToOne(targetEntity=ShortUrl::class)
     * @ORM\JoinColumn(name="short_url_id", referencedColumnName="id")
     */
    protected $shortUrl;
    /**
     * @var VisitLocation
     * @ORM\ManyToOne(targetEntity=VisitLocation::class, cascade={"persist"})
     * @ORM\JoinColumn(name="visit_location_id", referencedColumnName="id", nullable=true)
     */
    protected $visitLocation;

    public function __construct()
    {
        $this->date = new \DateTime();
    }

    /**
     * @return string
     */
    public function getReferer(): string
    {
        return $this->referer;
    }

    /**
     * @param string $referer
     * @return $this
     */
    public function setReferer($referer): self
    {
        $this->referer = $referer;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDate(): \DateTime
    {
        return $this->date;
    }

    /**
     * @param \DateTime $date
     * @return $this
     */
    public function setDate($date): self
    {
        $this->date = $date;
        return $this;
    }

    /**
     * @return ShortUrl
     */
    public function getShortUrl(): ShortUrl
    {
        return $this->shortUrl;
    }

    /**
     * @param ShortUrl $shortUrl
     * @return $this
     */
    public function setShortUrl($shortUrl): self
    {
        $this->shortUrl = $shortUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getRemoteAddr(): string
    {
        return $this->remoteAddr;
    }

    /**
     * @param string $remoteAddr
     * @return $this
     */
    public function setRemoteAddr($remoteAddr): self
    {
        $this->remoteAddr = $remoteAddr;
        return $this;
    }

    /**
     * @return string
     */
    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    /**
     * @param string $userAgent
     * @return $this
     */
    public function setUserAgent($userAgent): self
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    /**
     * @return VisitLocation
     */
    public function getVisitLocation(): VisitLocation
    {
        return $this->visitLocation;
    }

    /**
     * @param VisitLocation $visitLocation
     * @return $this
     */
    public function setVisitLocation($visitLocation): self
    {
        $this->visitLocation = $visitLocation;
        return $this;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return array data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize(): array
    {
        return [
            'referer' => $this->referer,
            'date' => isset($this->date) ? $this->date->format(\DateTime::ATOM) : null,
            'remoteAddr' => $this->remoteAddr,
            'userAgent' => $this->userAgent,
            'visitLocation' => $this->visitLocation,
        ];
    }
}
