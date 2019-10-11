<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Entity;

use Cake\Chronos\Chronos;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Shlinkio\Shlink\Common\Entity\AbstractEntity;
use Shlinkio\Shlink\Core\Domain\Resolver\DomainResolverInterface;
use Shlinkio\Shlink\Core\Domain\Resolver\SimpleDomainResolver;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Zend\Diactoros\Uri;

use function array_reduce;
use function count;
use function Functional\contains;
use function Functional\invoke;
use function Shlinkio\Shlink\Core\generateRandomShortCode;

class ShortUrl extends AbstractEntity
{
    /** @var string */
    private $longUrl;
    /** @var string */
    private $shortCode;
    /** @var Chronos */
    private $dateCreated;
    /** @var Collection|Visit[] */
    private $visits;
    /** @var Collection|Tag[] */
    private $tags;
    /** @var Chronos|null */
    private $validSince;
    /** @var Chronos|null */
    private $validUntil;
    /** @var integer|null */
    private $maxVisits;
    /** @var Domain|null */
    private $domain;

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
        $this->shortCode = $meta->getCustomSlug() ?? generateRandomShortCode();
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
     * @return ShortUrl
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

    public function maxVisitsReached(): bool
    {
        return $this->maxVisits !== null && $this->getVisitsCount() >= $this->maxVisits;
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
        $hasAllTags = count($shortUrlTags) === count($tags) && array_reduce(
            $tags,
            function (bool $hasAllTags, string $tag) use ($shortUrlTags) {
                return $hasAllTags && contains($shortUrlTags, $tag);
            },
            true
        );

        return $hasAllTags;
    }
}
