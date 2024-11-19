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
        public readonly ShortUrl|null $shortUrl,
        public readonly VisitType $type,
        public readonly string $userAgent,
        public readonly string $referer,
        public readonly bool $potentialBot,
        public readonly string|null $remoteAddr = null,
        public readonly string|null $visitedUrl = null,
        public readonly string|null $redirectUrl = null,
        private VisitLocation|null $visitLocation = null,
        public readonly Chronos $date = new Chronos(),
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

    private static function fromVisitor(
        ShortUrl|null $shortUrl,
        VisitType $type,
        Visitor $visitor,
        bool $anonymize,
    ): self {
        $geolocation = $visitor->geolocation;
        return new self(
            shortUrl: $shortUrl,
            type: $type,
            userAgent: $visitor->userAgent,
            referer: $visitor->referer,
            potentialBot: $visitor->potentialBot,
            remoteAddr: self::processAddress($visitor->remoteAddress, $anonymize),
            visitedUrl: $visitor->visitedUrl,
            redirectUrl: $visitor->redirectUrl,
            visitLocation: $geolocation !== null ? VisitLocation::fromGeolocation($geolocation) : null,
        );
    }

    private static function processAddress(string|null $address, bool $anonymize): string|null
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
        ShortUrl|null $shortUrl = null,
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

    public function getVisitLocation(): VisitLocation|null
    {
        return $this->visitLocation;
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

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @phpstan-type VisitedShortUrl array{shortCode: string, domain: string|null, shortUrl: string}
     * @param (callable(ShortUrl $shortUrl): VisitedShortUrl)|null $visitedShortUrlToArray
     */
    public function toArray(callable|null $visitedShortUrlToArray = null): array
    {
        $base = [
            'referer' => $this->referer,
            'date' => $this->date->toAtomString(),
            'userAgent' => $this->userAgent,
            'visitLocation' => $this->visitLocation,
            'potentialBot' => $this->potentialBot,
            'visitedUrl' => $this->visitedUrl,
            'redirectUrl' => $this->redirectUrl,
        ];
        if ($this->shortUrl !== null) {
            return $visitedShortUrlToArray === null ? $base : [
                ...$base,
                'visitedShortUrl' => $visitedShortUrlToArray($this->shortUrl),
            ];
        }

        return [
            ...$base,
            'type' => $this->type->value,
        ];
    }
}
