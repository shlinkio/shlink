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
use Shlinkio\Shlink\Importer\Model\ImportedShlinkVisit;

class Visit extends AbstractEntity implements JsonSerializable
{
    public const TYPE_VALID_SHORT_URL = 'valid_short_url';
    public const TYPE_IMPORTED = 'imported';
    public const TYPE_INVALID_SHORT_URL = 'invalid_short_url';
    public const TYPE_BASE_URL = 'base_url';
    public const TYPE_REGULAR_404 = 'regular_404';

    private string $referer;
    private Chronos $date;
    private ?string $remoteAddr = null;
    private ?string $visitedUrl = null;
    private string $userAgent;
    private string $type;
    private ?ShortUrl $shortUrl;
    private ?VisitLocation $visitLocation = null;

    private function __construct(?ShortUrl $shortUrl, string $type)
    {
        $this->shortUrl = $shortUrl;
        $this->date = Chronos::now();
        $this->type = $type;
    }

    public static function forValidShortUrl(ShortUrl $shortUrl, Visitor $visitor, bool $anonymize = true): self
    {
        $instance = new self($shortUrl, self::TYPE_VALID_SHORT_URL);
        $instance->hydrateFromVisitor($visitor, $anonymize);

        return $instance;
    }

    public static function fromImport(ShortUrl $shortUrl, ImportedShlinkVisit $importedVisit): self
    {
        $instance = new self($shortUrl, self::TYPE_IMPORTED);
        $instance->userAgent = $importedVisit->userAgent();
        $instance->referer = $importedVisit->referer();
        $instance->date = Chronos::instance($importedVisit->date());

        $importedLocation = $importedVisit->location();
        $instance->visitLocation = $importedLocation !== null ? VisitLocation::fromImport($importedLocation) : null;

        return $instance;
    }

    public static function forBasePath(Visitor $visitor, bool $anonymize = true): self
    {
        $instance = new self(null, self::TYPE_BASE_URL);
        $instance->hydrateFromVisitor($visitor, $anonymize);

        return $instance;
    }

    public static function forInvalidShortUrl(Visitor $visitor, bool $anonymize = true): self
    {
        $instance = new self(null, self::TYPE_INVALID_SHORT_URL);
        $instance->hydrateFromVisitor($visitor, $anonymize);

        return $instance;
    }

    public static function forRegularNotFound(Visitor $visitor, bool $anonymize = true): self
    {
        $instance = new self(null, self::TYPE_REGULAR_404);
        $instance->hydrateFromVisitor($visitor, $anonymize);

        return $instance;
    }

    private function hydrateFromVisitor(Visitor $visitor, bool $anonymize = true): void
    {
        $this->userAgent = $visitor->getUserAgent();
        $this->referer = $visitor->getReferer();
        $this->remoteAddr = $this->processAddress($anonymize, $visitor->getRemoteAddress());
        $this->visitedUrl = $visitor->getVisitedUrl();
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

    /**
     * Needed only for ArrayCollections to be able to apply criteria filtering
     * @internal
     */
    public function getType(): string
    {
        return $this->type();
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
