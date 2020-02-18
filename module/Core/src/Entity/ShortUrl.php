<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Entity;

use Cake\Chronos\Chronos;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Laminas\Diactoros\Uri;
use Shlinkio\Shlink\Common\Entity\AbstractEntity;
use Shlinkio\Shlink\Core\Domain\Resolver\DomainResolverInterface;
use Shlinkio\Shlink\Core\Domain\Resolver\SimpleDomainResolver;
use Shlinkio\Shlink\Core\Exception\ShortCodeCannotBeRegeneratedException;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;

use function array_reduce;
use function count;
use function Functional\contains;
use function Functional\invoke;
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
    private ?Domain $domain;
    private bool $customSlugWasProvided;
    private int $shortCodeLength;

    public function __construct(
        string $longUrl,
        ?ShortUrlMeta $meta = null,
        ?DomainResolverInterface $domainResolver = null
    ) {
        $meta = $meta ?? ShortUrlMeta::createEmpty();

        $this->longUrl = $longUrl;
        $this->dateCreated = Chronos::now();
        $this->visits = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->validSince = $meta->getValidSince();
        $this->validUntil = $meta->getValidUntil();
        $this->maxVisits = $meta->getMaxVisits();
        $this->customSlugWasProvided = $meta->hasCustomSlug();
        $this->shortCodeLength = $meta->getShortCodeLength();
        $this->shortCode = $meta->getCustomSlug() ?? generateRandomShortCode($this->shortCodeLength);
        $this->domain = ($domainResolver ?? new SimpleDomainResolver())->resolveDomain($meta->getDomain());
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

    /**
     * @param Collection|Tag[] $tags
     */
    public function setTags(Collection $tags): self
    {
        $this->tags = $tags;
        return $this;
    }

    public function updateMeta(ShortUrlMeta $shortCodeMeta): void
    {
        if ($shortCodeMeta->hasValidSince()) {
            $this->validSince = $shortCodeMeta->getValidSince();
        }
        if ($shortCodeMeta->hasValidUntil()) {
            $this->validUntil = $shortCodeMeta->getValidUntil();
        }
        if ($shortCodeMeta->hasMaxVisits()) {
            $this->maxVisits = $shortCodeMeta->getMaxVisits();
        }
    }

    /**
     * @throws ShortCodeCannotBeRegeneratedException
     */
    public function regenerateShortCode(): self
    {
        // In ShortUrls where a custom slug was provided, do nothing
        if ($this->customSlugWasProvided) {
            throw ShortCodeCannotBeRegeneratedException::forShortUrlWithCustomSlug();
        }

        // The short code can be regenerated only on ShortUrl which have not been persisted yet
        if ($this->id !== null) {
            throw ShortCodeCannotBeRegeneratedException::forShortUrlAlreadyPersisted();
        }

        $this->shortCode = generateRandomShortCode($this->shortCodeLength);
        return $this;
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
        return (string) (new Uri())->withPath($this->shortCode)
                                   ->withScheme($domainConfig['schema'] ?? 'http')
                                   ->withHost($this->resolveDomain($domainConfig['hostname'] ?? ''));
    }

    private function resolveDomain(string $fallback = ''): string
    {
        if ($this->domain === null) {
            return $fallback;
        }

        return $this->domain->getAuthority();
    }

    public function matchesCriteria(ShortUrlMeta $meta, array $tags): bool
    {
        if ($meta->hasMaxVisits() && $meta->getMaxVisits() !== $this->maxVisits) {
            return false;
        }
        if ($meta->hasDomain() && $meta->getDomain() !== $this->resolveDomain()) {
            return false;
        }
        if ($meta->hasValidSince() && ! $meta->getValidSince()->eq($this->validSince)) {
            return false;
        }
        if ($meta->hasValidUntil() && ! $meta->getValidUntil()->eq($this->validUntil)) {
            return false;
        }

        $shortUrlTags = invoke($this->getTags(), '__toString');
        return count($shortUrlTags) === count($tags) && array_reduce(
            $tags,
            fn (bool $hasAllTags, string $tag) => $hasAllTags && contains($shortUrlTags, $tag),
            true,
        );
    }
}
