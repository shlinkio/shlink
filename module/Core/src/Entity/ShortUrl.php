<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Entity;

use Cake\Chronos\Chronos;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Shlinkio\Shlink\Common\Entity\AbstractEntity;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;

use function count;

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

    public function __construct(string $longUrl, ?ShortUrlMeta $meta = null)
    {
        $meta = $meta ?? ShortUrlMeta::createEmpty();

        $this->longUrl = $longUrl;
        $this->dateCreated = Chronos::now();
        $this->visits = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->validSince = $meta->getValidSince();
        $this->validUntil = $meta->getValidUntil();
        $this->maxVisits = $meta->getMaxVisits();
        $this->shortCode = $meta->getCustomSlug() ?? ''; // TODO logic to calculate short code should be passed somehow
        $this->domain = $meta->hasDomain() ? new Domain($meta->getDomain()) : null;
    }

    public function getLongUrl(): string
    {
        return $this->longUrl;
    }

    public function getShortCode(): string
    {
        return $this->shortCode;
    }

    // TODO Short code is currently calculated based on the ID, so a setter is needed
    public function setShortCode(string $shortCode): self
    {
        $this->shortCode = $shortCode;
        return $this;
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

    public function domain(string $fallback = ''): string
    {
        if ($this->domain === null) {
            return $fallback;
        }

        return $this->domain->getAuthority();
    }
}
