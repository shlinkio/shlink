<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Entity;

use Cake\Chronos\Chronos;
use JsonSerializable;
use Shlinkio\Shlink\Common\Entity\AbstractEntity;
use Shlinkio\Shlink\Common\Exception\InvalidArgumentException;
use Shlinkio\Shlink\Common\Util\IpAddress;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Visit\Model\VisitLocationInterface;

class Visit extends AbstractEntity implements JsonSerializable
{
    private string $referer;
    private Chronos $date;
    private ?string $remoteAddr = null;
    private string $userAgent;
    private ShortUrl $shortUrl;
    private ?VisitLocation $visitLocation = null;

    public function __construct(ShortUrl $shortUrl, Visitor $visitor, bool $anonymize = true, ?Chronos $date = null)
    {
        $this->shortUrl = $shortUrl;
        $this->date = $date ?? Chronos::now();
        $this->userAgent = $visitor->getUserAgent();
        $this->referer = $visitor->getReferer();
        $this->remoteAddr = $this->processAddress($anonymize, $visitor->getRemoteAddress());
    }

    private function processAddress(bool $anonymize, ?string $address): ?string
    {
        // Localhost addresses do not need to be anonymized
        if (! $anonymize || $address === null || $address === IpAddress::LOCALHOST) {
            return $address;
        }

        try {
            return (string) IpAddress::fromString($address)->getAnonymizedCopy();
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

    public function getVisitLocation(): ?VisitLocationInterface
    {
        return $this->visitLocation;
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
            'date' => $this->date->toAtomString(),
            'userAgent' => $this->userAgent,
            'visitLocation' => $this->visitLocation,
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
