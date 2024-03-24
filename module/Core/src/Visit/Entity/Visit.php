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
    private function __construct(
        public readonly ?ShortUrl $shortUrl,
        public readonly VisitType $type,
        public readonly string $userAgent,
        public readonly string $referer,
        public readonly bool $potentialBot,
        public readonly ?string $remoteAddr = null,
        public readonly ?string $visitedUrl = null,
        private ?VisitLocation $visitLocation = null,
        // TODO Make public readonly once VisitRepositoryTest does not try to set it
        private Chronos $date = new Chronos(),
    ) {
    }

    public static function forValidShortUrl(ShortUrl $shortUrl, Visitor $visitor, bool $anonymize = true): self
    {
        return self::fromVisitor($shortUrl, VisitType::VALID_SHORT_URL, $visitor, $anonymize);
    }

    public static function forBasePath(Visitor $visitor, bool $anonymize = true): self
    {
        return self::fromVisitor(null, VisitType::BASE_URL, $visitor, $anonymize);
    }

    public static function forInvalidShortUrl(Visitor $visitor, bool $anonymize = true): self
    {
        return self::fromVisitor(null, VisitType::INVALID_SHORT_URL, $visitor, $anonymize);
    }

    public static function forRegularNotFound(Visitor $visitor, bool $anonymize = true): self
    {
        return self::fromVisitor(null, VisitType::REGULAR_404, $visitor, $anonymize);
    }

    private static function fromVisitor(?ShortUrl $shortUrl, VisitType $type, Visitor $visitor, bool $anonymize): self
    {
        return new self(
            shortUrl: $shortUrl,
            type: $type,
            userAgent: $visitor->userAgent,
            referer: $visitor->referer,
            potentialBot: $visitor->isPotentialBot(),
            remoteAddr: self::processAddress($visitor->remoteAddress, $anonymize),
            visitedUrl: $visitor->visitedUrl,
        );
    }

    private static function processAddress(?string $address, bool $anonymize): ?string
    {
        // Localhost address does not need to be anonymized
        if (! $anonymize || $address === null || $address === IpAddress::LOCALHOST) {
            return $address;
        }

        try {
            return IpAddress::fromString($address)->getAnonymizedCopy()->__toString();
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    public static function fromImport(ShortUrl $shortUrl, ImportedShlinkVisit $importedVisit): self
    {
        return self::fromImportOrOrphanImport($importedVisit, VisitType::IMPORTED, $shortUrl);
    }

    public static function fromOrphanImport(ImportedShlinkOrphanVisit $importedVisit): self
    {
        return self::fromImportOrOrphanImport(
            $importedVisit,
            VisitType::tryFrom($importedVisit->type) ?? VisitType::IMPORTED,
        );
    }

    private static function fromImportOrOrphanImport(
        ImportedShlinkVisit|ImportedShlinkOrphanVisit $importedVisit,
        VisitType $type,
        ?ShortUrl $shortUrl = null,
    ): self {
        $importedLocation = $importedVisit->location;
        return new self(
            shortUrl: $shortUrl,
            type: $type,
            userAgent: $importedVisit->userAgent,
            referer: $importedVisit->referer,
            potentialBot: isCrawler($importedVisit->userAgent),
            visitedUrl: $importedVisit instanceof ImportedShlinkOrphanVisit ? $importedVisit->visitedUrl : null,
            visitLocation: $importedLocation !== null ? VisitLocation::fromImport($importedLocation) : null,
            date: normalizeDate($importedVisit->date),
        );
    }

    public function hasRemoteAddr(): bool
    {
        return ! empty($this->remoteAddr);
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

    /**
     * Needed only for ArrayCollections to be able to apply criteria filtering
     * @internal
     */
    public function getType(): VisitType
    {
        return $this->type;
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
        $base = [
            'referer' => $this->referer,
            'date' => $this->date->toAtomString(),
            'userAgent' => $this->userAgent,
            'visitLocation' => $this->visitLocation,
            'potentialBot' => $this->potentialBot,
            'visitedUrl' => $this->visitedUrl,
        ];
        if (! $this->isOrphan()) {
            return $base;
        }

        return [
            ...$base,
            'type' => $this->type->value,
        ];
    }
}
