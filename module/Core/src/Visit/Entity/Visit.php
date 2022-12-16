<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Entity;

use Cake\Chronos\Chronos;
use JsonSerializable;
use Shlinkio\Shlink\Common\Entity\AbstractEntity;
use Shlinkio\Shlink\Common\Exception\InvalidArgumentException;
use Shlinkio\Shlink\Common\Util\IpAddress;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Shlinkio\Shlink\Core\Visit\Model\VisitType;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkOrphanVisit;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkVisit;

use function Shlinkio\Shlink\Core\isCrawler;
use function Shlinkio\Shlink\Core\normalizeDate;

class Visit extends AbstractEntity implements JsonSerializable
{
    private string $referer;
    private Chronos $date;
    private ?string $remoteAddr = null;
    private ?string $visitedUrl = null;
    private string $userAgent;
    private VisitType $type;
    private ?ShortUrl $shortUrl;
    private ?VisitLocation $visitLocation = null;
    private bool $potentialBot;

    private function __construct(?ShortUrl $shortUrl, VisitType $type)
    {
        $this->shortUrl = $shortUrl;
        $this->date = Chronos::now();
        $this->type = $type;
    }

    public static function forValidShortUrl(ShortUrl $shortUrl, Visitor $visitor, bool $anonymize = true): self
    {
        $instance = new self($shortUrl, VisitType::VALID_SHORT_URL);
        $instance->hydrateFromVisitor($visitor, $anonymize);

        return $instance;
    }

    public static function fromImport(ShortUrl $shortUrl, ImportedShlinkVisit $importedVisit): self
    {
        return self::fromImportOrOrphanImport($importedVisit, VisitType::IMPORTED, $shortUrl);
    }

    public static function fromOrphanImport(ImportedShlinkOrphanVisit $importedVisit): self
    {
        $instance = self::fromImportOrOrphanImport(
            $importedVisit,
            VisitType::tryFrom($importedVisit->type) ?? VisitType::IMPORTED,
        );
        $instance->visitedUrl = $importedVisit->visitedUrl;

        return $instance;
    }

    private static function fromImportOrOrphanImport(
        ImportedShlinkVisit|ImportedShlinkOrphanVisit $importedVisit,
        VisitType $type,
        ?ShortUrl $shortUrl = null,
    ): self {
        $instance = new self($shortUrl, $type);
        $instance->userAgent = $importedVisit->userAgent;
        $instance->potentialBot = isCrawler($instance->userAgent);
        $instance->referer = $importedVisit->referer;
        $instance->date = normalizeDate($importedVisit->date);

        $importedLocation = $importedVisit->location;
        $instance->visitLocation = $importedLocation !== null ? VisitLocation::fromImport($importedLocation) : null;

        return $instance;
    }

    public static function forBasePath(Visitor $visitor, bool $anonymize = true): self
    {
        $instance = new self(null, VisitType::BASE_URL);
        $instance->hydrateFromVisitor($visitor, $anonymize);

        return $instance;
    }

    public static function forInvalidShortUrl(Visitor $visitor, bool $anonymize = true): self
    {
        $instance = new self(null, VisitType::INVALID_SHORT_URL);
        $instance->hydrateFromVisitor($visitor, $anonymize);

        return $instance;
    }

    public static function forRegularNotFound(Visitor $visitor, bool $anonymize = true): self
    {
        $instance = new self(null, VisitType::REGULAR_404);
        $instance->hydrateFromVisitor($visitor, $anonymize);

        return $instance;
    }

    private function hydrateFromVisitor(Visitor $visitor, bool $anonymize = true): void
    {
        $this->userAgent = $visitor->userAgent;
        $this->referer = $visitor->referer;
        $this->remoteAddr = $this->processAddress($anonymize, $visitor->remoteAddress);
        $this->visitedUrl = $visitor->visitedUrl;
        $this->potentialBot = $visitor->isPotentialBot();
    }

    private function processAddress(bool $anonymize, ?string $address): ?string
    {
        // Localhost addresses do not need to be anonymized
        if (! $anonymize || $address === null || $address === IpAddress::LOCALHOST) {
            return $address;
        }

        try {
            return IpAddress::fromString($address)->getAnonymizedCopy()->__toString();
        } catch (InvalidArgumentException) {
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

    public function getVisitLocation(): ?VisitLocation
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

    public function type(): VisitType
    {
        return $this->type;
    }

    /**
     * Needed only for ArrayCollections to be able to apply criteria filtering
     * @internal
     */
    public function getType(): VisitType
    {
        return $this->type();
    }

    /**
     * @internal
     */
    public function getDate(): Chronos
    {
        return $this->date;
    }

    public function jsonSerialize(): array
    {
        return [
            'referer' => $this->referer,
            'date' => $this->date->toAtomString(),
            'userAgent' => $this->userAgent,
            'visitLocation' => $this->visitLocation,
            'potentialBot' => $this->potentialBot,
        ];
    }
}
