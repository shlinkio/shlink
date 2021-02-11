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
    public const TYPE_VALID_SHORT_URL = 'valid_short_url';
    public const TYPE_INVALID_SHORT_URL = 'invalid_short_url';
    public const TYPE_BASE_URL = 'base_url';
    public const TYPE_REGULAR_404 = 'regular_404';

    private string $referer;
    private Chronos $date;
    private ?string $remoteAddr;
    private ?string $visitedUrl;
    private string $userAgent;
    private string $type;
    private ?ShortUrl $shortUrl;
    private ?VisitLocation $visitLocation = null;

    private function __construct(?ShortUrl $shortUrl, Visitor $visitor, string $type, bool $anonymize = true)
    {
        $this->shortUrl = $shortUrl;
        $this->date = Chronos::now();
        $this->userAgent = $visitor->getUserAgent();
        $this->referer = $visitor->getReferer();
        $this->remoteAddr = $this->processAddress($anonymize, $visitor->getRemoteAddress());
        $this->visitedUrl = $visitor->getVisitedUrl();
        $this->type = $type;
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

    public static function forValidShortUrl(ShortUrl $shortUrl, Visitor $visitor, bool $anonymize = true): self
    {
        return new self($shortUrl, $visitor, self::TYPE_VALID_SHORT_URL, $anonymize);
    }

    public static function forBasePath(Visitor $visitor, bool $anonymize = true): self
    {
        return new self(null, $visitor, self::TYPE_BASE_URL, $anonymize);
    }

    public static function forInvalidShortUrl(Visitor $visitor, bool $anonymize = true): self
    {
        return new self(null, $visitor, self::TYPE_INVALID_SHORT_URL, $anonymize);
    }

    public static function forRegularNotFound(Visitor $visitor, bool $anonymize = true): self
    {
        return new self(null, $visitor, self::TYPE_REGULAR_404, $anonymize);
    }

    public function getRemoteAddr(): ?string
    {
        return $this->remoteAddr;
    }

    public function hasRemoteAddr(): bool
    {
        return ! empty($this->remoteAddr);
    }

    public function getShortUrl(): ?ShortUrl
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

    public function isOrphan(): bool
    {
        return $this->shortUrl === null;
    }

    public function visitedUrl(): ?string
    {
        return $this->visitedUrl;
    }

    public function type(): string
    {
        return $this->type;
    }

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
