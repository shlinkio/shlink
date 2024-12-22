<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Entity;

use Cake\Chronos\Chronos;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use Shlinkio\Shlink\Common\Entity\AbstractEntity;
use Shlinkio\Shlink\Core\Domain\Entity\Domain;
use Shlinkio\Shlink\Core\Exception\ShortCodeCannotBeRegeneratedException;
use Shlinkio\Shlink\Core\RedirectRule\Entity\ShortUrlRedirectRule;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlEdition;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlMode;
use Shlinkio\Shlink\Core\ShortUrl\Model\Validation\ShortUrlInputFilter;
use Shlinkio\Shlink\Core\ShortUrl\Resolver\ShortUrlRelationResolverInterface;
use Shlinkio\Shlink\Core\ShortUrl\Resolver\SimpleShortUrlRelationResolver;
use Shlinkio\Shlink\Core\Tag\Entity\Tag;
use Shlinkio\Shlink\Core\Visit\Entity\ShortUrlVisitsCount;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Model\VisitsSummary;
use Shlinkio\Shlink\Core\Visit\Model\VisitType;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkUrl;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

use function array_map;
use function count;
use function Shlinkio\Shlink\Core\generateRandomShortCode;
use function Shlinkio\Shlink\Core\normalizeDate;
use function Shlinkio\Shlink\Core\normalizeOptionalDate;
use function sprintf;

class ShortUrl extends AbstractEntity
{
    /**
     * @param Collection<int, Tag> $tags
     * @param Collection<int, Visit> & Selectable<int, Visit> $visits
     * @param Collection<int, ShortUrlVisitsCount> & Selectable<int, ShortUrlVisitsCount> $visitsCounts
     * @param Collection<int, ShortUrlRedirectRule> $redirectRules
     */
    private function __construct(
        private string $longUrl,
        private string $shortCode,
        private Chronos $dateCreated = new Chronos(),
        private Collection $tags = new ArrayCollection(),
        private Collection & Selectable $visits = new ArrayCollection(),
        private Collection & Selectable $visitsCounts = new ArrayCollection(),
        private Chronos|null $validSince = null,
        private Chronos|null $validUntil = null,
        private int|null $maxVisits = null,
        private Domain|null $domain = null,
        private bool $customSlugWasProvided = false,
        private int $shortCodeLength = 0,
        public readonly ApiKey|null $authorApiKey = null,
        private string|null $title = null,
        private bool $titleWasAutoResolved = false,
        private bool $crawlable = false,
        private bool $forwardQuery = true,
        private string|null $importSource = null,
        private string|null $importOriginalShortCode = null,
        private Collection $redirectRules = new ArrayCollection(),
    ) {
    }

    /**
     * @internal
     */
    public static function createFake(): self
    {
        return self::withLongUrl('https://foo');
    }

    /**
     * @param non-empty-string $longUrl
     */
    public static function withLongUrl(string $longUrl): self
    {
        return self::create(ShortUrlCreation::fromRawData([ShortUrlInputFilter::LONG_URL => $longUrl]));
    }

    public static function create(
        ShortUrlCreation $creation,
        ShortUrlRelationResolverInterface|null $relationResolver = null,
    ): self {
        $relationResolver = $relationResolver ?? new SimpleShortUrlRelationResolver();
        $shortCodeLength = $creation->shortCodeLength;

        return new self(
            longUrl: $creation->getLongUrl(),
            shortCode: sprintf(
                '%s%s',
                $creation->pathPrefix ?? '',
                $creation->customSlug ?? generateRandomShortCode($shortCodeLength, $creation->shortUrlMode),
            ),
            tags: $relationResolver->resolveTags($creation->tags),
            validSince: $creation->validSince,
            validUntil: $creation->validUntil,
            maxVisits: $creation->maxVisits,
            domain: $relationResolver->resolveDomain($creation->domain),
            customSlugWasProvided: $creation->hasCustomSlug(),
            shortCodeLength: $shortCodeLength,
            authorApiKey: $creation->apiKey,
            title: $creation->title,
            titleWasAutoResolved: $creation->titleWasAutoResolved,
            crawlable: $creation->crawlable,
            forwardQuery: $creation->forwardQuery,
        );
    }

    public static function fromImport(
        ImportedShlinkUrl $url,
        bool $importShortCode,
        ShortUrlRelationResolverInterface|null $relationResolver = null,
    ): self {
        $meta = [
            ShortUrlInputFilter::LONG_URL => $url->longUrl,
            ShortUrlInputFilter::DOMAIN => $url->domain,
            ShortUrlInputFilter::TAGS => $url->tags,
            ShortUrlInputFilter::TITLE => $url->title,
            ShortUrlInputFilter::MAX_VISITS => $url->meta->maxVisits,
        ];
        if ($importShortCode) {
            $meta[ShortUrlInputFilter::CUSTOM_SLUG] = $url->shortCode;
        }

        $instance = self::create(ShortUrlCreation::fromRawData($meta), $relationResolver);

        $instance->validSince = normalizeOptionalDate($url->meta->validSince);
        $instance->validUntil = normalizeOptionalDate($url->meta->validUntil);
        $instance->dateCreated = normalizeDate($url->createdAt);
        $instance->importSource = $url->source->value;
        $instance->importOriginalShortCode = $url->shortCode;

        return $instance;
    }

    public function update(
        ShortUrlEdition $shortUrlEdit,
        ShortUrlRelationResolverInterface|null $relationResolver = null,
    ): void {
        if ($shortUrlEdit->validSinceWasProvided()) {
            $this->validSince = $shortUrlEdit->validSince;
        }
        if ($shortUrlEdit->validUntilWasProvided()) {
            $this->validUntil = $shortUrlEdit->validUntil;
        }
        if ($shortUrlEdit->maxVisitsWasProvided()) {
            $this->maxVisits = $shortUrlEdit->maxVisits;
        }
        if ($shortUrlEdit->longUrlWasProvided()) {
            $this->longUrl = $shortUrlEdit->longUrl ?? $this->longUrl;
        }
        if ($shortUrlEdit->tagsWereProvided()) {
            $relationResolver = $relationResolver ?? new SimpleShortUrlRelationResolver();
            $this->tags = $relationResolver->resolveTags($shortUrlEdit->tags);
        }
        if ($shortUrlEdit->crawlableWasProvided()) {
            $this->crawlable = $shortUrlEdit->crawlable;
        }
        if (
            $this->title === null
            || $shortUrlEdit->titleWasProvided()
            || ($this->titleWasAutoResolved && $shortUrlEdit->titleWasAutoResolved())
        ) {
            $this->title = $shortUrlEdit->title;
            $this->titleWasAutoResolved = $shortUrlEdit->titleWasAutoResolved();
        }
        if ($shortUrlEdit->forwardQueryWasProvided()) {
            $this->forwardQuery = $shortUrlEdit->forwardQuery;
        }
    }

    public function getLongUrl(): string
    {
        return $this->longUrl;
    }

    public function getShortCode(): string
    {
        return $this->shortCode;
    }

    public function getDomain(): Domain|null
    {
        return $this->domain;
    }

    public function forwardQuery(): bool
    {
        return $this->forwardQuery;
    }

    public function title(): string|null
    {
        return $this->title;
    }

    public function dateCreated(): Chronos
    {
        return $this->dateCreated;
    }

    public function reachedVisits(int $visitsAmount): bool
    {
        return count($this->visits) >= $visitsAmount;
    }

    public function mostRecentImportedVisitDate(): Chronos|null
    {
        $criteria = Criteria::create()->where(Criteria::expr()->eq('type', VisitType::IMPORTED))
                                      ->orderBy(['id' => 'DESC'])
                                      ->setMaxResults(1);
        $visit = $this->visits->matching($criteria)->last();

        return $visit instanceof Visit ? $visit->date : null;
    }

    /**
     * @param Collection<int, Visit> & Selectable<int, Visit> $visits
     * @internal
     */
    public function setVisits(Collection & Selectable $visits): self
    {
        $this->visits = $visits;
        return $this;
    }

    /**
     * @throws ShortCodeCannotBeRegeneratedException
     */
    public function regenerateShortCode(ShortUrlMode $mode): void
    {
        // In ShortUrls where a custom slug was provided, throw error, unless it is an imported one
        if ($this->customSlugWasProvided && $this->importSource === null) {
            throw ShortCodeCannotBeRegeneratedException::forShortUrlWithCustomSlug();
        }

        // The short code can be regenerated only on ShortUrl which have not been persisted yet
        if ($this->id !== null) {
            throw ShortCodeCannotBeRegeneratedException::forShortUrlAlreadyPersisted();
        }

        $this->shortCode = generateRandomShortCode($this->shortCodeLength, $mode);
    }

    public function isEnabled(): bool
    {
        $maxVisitsReached = $this->maxVisits !== null && $this->reachedVisits($this->maxVisits);
        if ($maxVisitsReached) {
            return false;
        }

        $now = Chronos::now();
        $beforeValidSince = $this->validSince !== null && $this->validSince->greaterThan($now);
        if ($beforeValidSince) {
            return false;
        }

        $afterValidUntil = $this->validUntil !== null && $this->validUntil->lessThan($now);
        if ($afterValidUntil) {
            return false;
        }

        return true;
    }

    /**
     * @param null|(callable(): ?string) $getAuthority -
     *  This is a callback so that we trust its return value if provided, even if it is null.
     *  Providing the raw authority as `string|null` would result in a fallback to `$this->domain` when the authority
     *  was null.
     */
    public function toArray(VisitsSummary|null $precalculatedSummary = null, callable|null $getAuthority = null): array
    {
        return [
            'shortCode' => $this->shortCode,
            'longUrl' => $this->longUrl,
            'dateCreated' => $this->dateCreated->toAtomString(),
            'tags' => array_map(static fn (Tag $tag) => $tag->__toString(), $this->tags->toArray()),
            'meta' => [
                'validSince' => $this->validSince?->toAtomString(),
                'validUntil' => $this->validUntil?->toAtomString(),
                'maxVisits' => $this->maxVisits,
            ],
            'domain' => $getAuthority !== null ? $getAuthority() : $this->domain?->authority,
            'title' => $this->title,
            'crawlable' => $this->crawlable,
            'forwardQuery' => $this->forwardQuery,
            'visitsSummary' => $precalculatedSummary ?? VisitsSummary::fromTotalAndNonBots(
                count($this->visits),
                count($this->visits->matching(
                    Criteria::create()->where(Criteria::expr()->eq('potentialBot', false)),
                )),
            ),
            'hasRedirectRules' => count($this->redirectRules) > 0,
        ];
    }
}
