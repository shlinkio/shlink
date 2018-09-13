<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Entity;

use Doctrine\ORM\Mapping as ORM;
use Shlinkio\Shlink\Common\Entity\AbstractEntity;
use Shlinkio\Shlink\Common\Exception\WrongIpException;
use Shlinkio\Shlink\Common\Util\IpAddress;

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
    private $referer;
    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=false)
     */
    private $date;
    /**
     * @var string
     * @ORM\Column(type="string", length=256, name="remote_addr", nullable=true)
     */
    private $remoteAddr;
    /**
     * @var string
     */
    private $remoteAddrHash;
    /**
     * @var string
     * @ORM\Column(type="string", length=256, name="user_agent", nullable=true)
     */
    private $userAgent;
    /**
     * @var ShortUrl
     * @ORM\ManyToOne(targetEntity=ShortUrl::class)
     * @ORM\JoinColumn(name="short_url_id", referencedColumnName="id")
     */
    private $shortUrl;
    /**
     * @var VisitLocation
     * @ORM\ManyToOne(targetEntity=VisitLocation::class, cascade={"persist"})
     * @ORM\JoinColumn(name="visit_location_id", referencedColumnName="id", nullable=true)
     */
    private $visitLocation;

    public function __construct()
    {
        $this->date = new \DateTime();
    }

    public function getReferer(): string
    {
        return $this->referer;
    }

    public function setReferer(string $referer): self
    {
        $this->referer = $referer;
        return $this;
    }

    public function getDate(): \DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function getShortUrl(): ShortUrl
    {
        return $this->shortUrl;
    }

    public function setShortUrl(ShortUrl $shortUrl): self
    {
        $this->shortUrl = $shortUrl;
        return $this;
    }

    public function getRemoteAddr(): string
    {
        return $this->remoteAddr;
    }

    public function setRemoteAddr(?string $remoteAddr): self
    {
        $this->remoteAddr = $this->obfuscateAddress($remoteAddr);
        $this->remoteAddrHash = $this->hashAddress($remoteAddr);

        return $this;
    }

    private function obfuscateAddress(?string $address): ?string
    {
        if ($address === null) {
            return null;
        }

        try {
            return (string) IpAddress::fromString($address)->getObfuscatedCopy();
        } catch (WrongIpException $e) {
            return null;
        }
    }

    private function hashAddress(?string $address): ?string
    {
        return $address ? \hash('sha256', $address) : null;
    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    public function setUserAgent(string $userAgent): self
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    public function getVisitLocation(): VisitLocation
    {
        return $this->visitLocation;
    }

    public function setVisitLocation(VisitLocation $visitLocation): self
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
