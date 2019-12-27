<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Entity;

use Cake\Chronos\Chronos;
use JsonSerializable;
use Shlinkio\Shlink\Common\Entity\AbstractEntity;
use Shlinkio\Shlink\Common\Exception\InvalidArgumentException;
use Shlinkio\Shlink\Common\Util\IpAddress;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Visit\Model\UnknownVisitLocation;
use Shlinkio\Shlink\Core\Visit\Model\VisitLocationInterface;

class Visit extends AbstractEntity implements JsonSerializable
{
    /** @var string */
    private $referer;
    /** @var Chronos */
    private $date;
    /** @var string|null */
    private $remoteAddr;
    /** @var string */
    private $userAgent;
    /** @var ShortUrl */
    private $shortUrl;
    /** @var VisitLocation */
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
        } catch (InvalidArgumentException $e) {
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

    public function getShortUrl(): ShortUrl
    {
        return $this->shortUrl;
    }

    public function getVisitLocation(): VisitLocationInterface
    {
        return $this->visitLocation ?? new UnknownVisitLocation();
    }

    public function isLocatable(): bool
    {
        return $this->hasRemoteAddr() && $this->remoteAddr !== IpAddress::LOCALHOST;
    }

    public function locate(VisitLocation $visitLocation): self
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
            'date' => $this->date !== null ? $this->date->toAtomString() : null,
            'userAgent' => $this->userAgent,
            'visitLocation' => $this->visitLocation,

            // Deprecated
            'remoteAddr' => null,
        ];
    }

    /**
     * @internal
     */
    public function getDate(): Chronos
    {
        return $this->date;
    }
}
