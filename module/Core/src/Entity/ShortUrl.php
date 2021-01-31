<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Entity;

use Cake\Chronos\Chronos;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Laminas\Diactoros\Uri;
use Shlinkio\Shlink\Common\Entity\AbstractEntity;
use Shlinkio\Shlink\Core\Exception\ShortCodeCannotBeRegeneratedException;
use Shlinkio\Shlink\Core\Model\ShortUrlEdit;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\ShortUrl\Resolver\ShortUrlRelationResolverInterface;
use Shlinkio\Shlink\Core\ShortUrl\Resolver\SimpleShortUrlRelationResolver;
use Shlinkio\Shlink\Core\Validation\ShortUrlInputFilter;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkUrl;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

use function count;
use function Shlinkio\Shlink\Core\generateRandomShortCode;

class ShortUrl extends AbstractEntity
{
    private string $longUrl;
    private string $shortCode;
    private Chronos $dateCreated;
    /** @var Collection|Visit[] */
    private Collection $visits;
    /** @var Collection|Tag[] */
    private Collection $tags;
    private ?Chronos $validSince = null;
    private ?Chronos $validUntil = null;
    private ?int $maxVisits = null;
    private ?Domain $domain = null;
    private bool $customSlugWasProvided;
    private int $shortCodeLength;
    private ?string $importSource = null;
    private ?string $importOriginalShortCode = null;
    private ?ApiKey $authorApiKey = null;

    private function __construct()
    {
    }

    public static function createEmpty(): self
    {
        return self::fromMeta(ShortUrlMeta::createEmpty());
    }

    public static function withLongUrl(string $longUrl): self
    {
        return self::fromMeta(ShortUrlMeta::fromRawData([ShortUrlInputFilter::LONG_URL => $longUrl]));
    }

    public static function fromMeta(
        ShortUrlMeta $meta,
        ?ShortUrlRelationResolverInterface $relationResolver = null
    ): self {
        $instance = new self();
        $relationResolver = $relationResolver ?? new SimpleShortUrlRelationResolver();

        $instance->longUrl = $meta->getLongUrl();
        $instance->dateCreated = Chronos::now();
        $instance->visits = new ArrayCollection();
        $instance->tags = $relationResolver->resolveTags($meta->getTags());
        $instance->validSince = $meta->getValidSince();
        $instance->validUntil = $meta->getValidUntil();
        $instance->maxVisits = $meta->getMaxVisits();
        $instance->customSlugWasProvided = $meta->hasCustomSlug();
        $instance->shortCodeLength = $meta->getShortCodeLength();
        $instance->shortCode = $meta->getCustomSlug() ?? generateRandomShortCode($instance->shortCodeLength);
        $instance->domain = $relationResolver->resolveDomain($meta->getDomain());
        $instance->authorApiKey = $meta->getApiKey();

        return $instance;
    }

    public static function fromImport(
        ImportedShlinkUrl $url,
        bool $importShortCode,
        ?ShortUrlRelationResolverInterface $relationResolver = null
    ): self {
        $meta = [
            ShortUrlInputFilter::LONG_URL => $url->longUrl(),
            ShortUrlInputFilter::DOMAIN => $url->domain(),
            ShortUrlInputFilter::TAGS => $url->tags(),
            ShortUrlInputFilter::VALIDATE_URL => false,
        ];
        if ($importShortCode) {
            $meta[ShortUrlInputFilter::CUSTOM_SLUG] = $url->shortCode();
        }

        $instance = self::fromMeta(ShortUrlMeta::fromRawData($meta), $relationResolver);
        $instance->importSource = $url->source();
        $instance->importOriginalShortCode = $url->shortCode();
        $instance->dateCreated = Chronos::instance($url->createdAt());

        return $instance;
    }

    public function getLongUrl(): string
    {
        return $this->longUrl;
    }

    public function getShortCode(): string
    {
        return $this->shortCode;
    }

    public function getDateCreated(): Chronos
    {
        return $this->dateCreated;
    }

    public function getDomain(): ?Domain
    {
        return $this->domain;
    }

    /**
     * @return Collection|Tag[]
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function update(
        ShortUrlEdit $shortUrlEdit,
        ?ShortUrlRelationResolverInterface $relationResolver = null
    ): void {
        if ($shortUrlEdit->hasValidSince()) {
            $this->validSince = $shortUrlEdit->validSince();
        }
        if ($shortUrlEdit->hasValidUntil()) {
            $this->validUntil = $shortUrlEdit->validUntil();
        }
        if ($shortUrlEdit->hasMaxVisits()) {
            $this->maxVisits = $shortUrlEdit->maxVisits();
        }
        if ($shortUrlEdit->hasLongUrl()) {
            $this->longUrl = $shortUrlEdit->longUrl();
        }
        if ($shortUrlEdit->hasTags()) {
            $relationResolver = $relationResolver ?? new SimpleShortUrlRelationResolver();
            $this->tags = $relationResolver->resolveTags($shortUrlEdit->tags());
        }
    }

    /**
     * @throws ShortCodeCannotBeRegeneratedException
     */
    public function regenerateShortCode(): void
    {
        // In ShortUrls where a custom slug was provided, throw error, unless it is an imported one
        if ($this->customSlugWasProvided && $this->importSource === null) {
            throw ShortCodeCannotBeRegeneratedException::forShortUrlWithCustomSlug();
        }

        // The short code can be regenerated only on ShortUrl which have not been persisted yet
        if ($this->id !== null) {
            throw ShortCodeCannotBeRegeneratedException::forShortUrlAlreadyPersisted();
        }

        $this->shortCode = generateRandomShortCode($this->shortCodeLength);
    }

    public function getValidSince(): ?Chronos
    {
        return $this->validSince;
    }

    public function getValidUntil(): ?Chronos
    {
        return $this->validUntil;
    }

    public function getVisitsCount(): int
    {
        return count($this->visits);
    }

    /**
     * @param Collection|Visit[] $visits
     * @internal
     */
    public function setVisits(Collection $visits): self
    {
        $this->visits = $visits;
        return $this;
    }

    public function getMaxVisits(): ?int
    {
        return $this->maxVisits;
    }

    public function isEnabled(): bool
    {
        $maxVisitsReached = $this->maxVisits !== null && $this->getVisitsCount() >= $this->maxVisits;
        if ($maxVisitsReached) {
            return false;
        }

        $now = Chronos::now();
        $beforeValidSince = $this->validSince !== null && $this->validSince->gt($now);
        if ($beforeValidSince) {
            return false;
        }

        $afterValidUntil = $this->validUntil !== null && $this->validUntil->lt($now);
        if ($afterValidUntil) {
            return false;
        }

        return true;
    }

    public function toString(array $domainConfig): string
    {
        return (new Uri())->withPath($this->shortCode)
                          ->withScheme($domainConfig['schema'] ?? 'http')
                          ->withHost($this->resolveDomain($domainConfig['hostname'] ?? ''))
                          ->__toString();
    }

    private function resolveDomain(string $fallback = ''): string
    {
        if ($this->domain === null) {
            return $fallback;
        }

        return $this->domain->getAuthority();
    }
}
