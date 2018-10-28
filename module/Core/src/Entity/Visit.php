<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Entity;

use Cake\Chronos\Chronos;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Shlinkio\Shlink\Common\Entity\AbstractEntity;
use Shlinkio\Shlink\Common\Exception\WrongIpException;
use Shlinkio\Shlink\Common\Util\IpAddress;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Repository\VisitRepository;

/**
 * Class Visit
 * @author
 * @link
 *
 * @ORM\Entity(repositoryClass=VisitRepository::class)
 * @ORM\Table(name="visits")
 */
class Visit extends AbstractEntity implements JsonSerializable
{
    /**
     * @var string
     * @ORM\Column(type="string", length=256, nullable=true)
     */
    private $referer;
    /**
     * @var Chronos
     * @ORM\Column(type="chronos_datetime", nullable=false)
     */
    private $date;
    /**
     * @var string|null
     * @ORM\Column(type="string", length=256, name="remote_addr", nullable=true)
     */
    private $remoteAddr;
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

    public function __construct(ShortUrl $shortUrl, Visitor $visitor, ?Chronos $date = null)
    {
        $this->shortUrl = $shortUrl;
        $this->date = $date ?? Chronos::now();
        $this->userAgent = $visitor->getUserAgent();
        $this->referer = $visitor->getReferer();
        $this->remoteAddr = $this->obfuscateAddress($visitor->getRemoteAddress());
    }

    private function obfuscateAddress(?string $address): ?string
    {
        // Localhost addresses do not need to be obfuscated
        if ($address === null || $address === IpAddress::LOCALHOST) {
            return $address;
        }

        try {
            return (string) IpAddress::fromString($address)->getObfuscatedCopy();
        } catch (WrongIpException $e) {
            return null;
        }
    }

    public function getRemoteAddr(): ?string
    {
        return $this->remoteAddr;
    }

    public function hasRemoteAddr(): bool
    {
        return ! empty($this->remoteAddr);
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
            'date' => isset($this->date) ? $this->date->toAtomString() : null,
            'userAgent' => $this->userAgent,
            'visitLocation' => $this->visitLocation,

            // Deprecated
            'remoteAddr' => null,
        ];
    }
}
