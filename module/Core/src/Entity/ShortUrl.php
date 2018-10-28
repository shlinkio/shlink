<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Entity;

use Cake\Chronos\Chronos;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Shlinkio\Shlink\Common\Entity\AbstractEntity;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepository;
use function count;

/**
 * Class ShortUrl
 * @author
 * @link
 *
 * @ORM\Entity(repositoryClass=ShortUrlRepository::class)
 * @ORM\Table(name="short_urls")
 */
class ShortUrl extends AbstractEntity
{
    /**
     * @var string
     * @ORM\Column(name="original_url", type="string", nullable=false, length=1024)
     */
    private $longUrl;
    /**
     * @var string
     * @ORM\Column(
     *     name="short_code",
     *     type="string",
     *     nullable=false,
     *     length=255,
     *     unique=true
     * )
     */
    private $shortCode;
    /**
     * @var Chronos
     * @ORM\Column(name="date_created", type="chronos_datetime")
     */
    private $dateCreated;
    /**
     * @var Collection|Visit[]
     * @ORM\OneToMany(targetEntity=Visit::class, mappedBy="shortUrl", fetch="EXTRA_LAZY")
     */
    private $visits;
    /**
     * @var Collection|Tag[]
     * @ORM\ManyToMany(targetEntity=Tag::class, cascade={"persist"})
     * @ORM\JoinTable(name="short_urls_in_tags", joinColumns={
     *     @ORM\JoinColumn(name="short_url_id", referencedColumnName="id")
     * }, inverseJoinColumns={
     *     @ORM\JoinColumn(name="tag_id", referencedColumnName="id")
     * })
     */
    private $tags;
    /**
     * @var Chronos|null
     * @ORM\Column(name="valid_since", type="chronos_datetime", nullable=true)
     */
    private $validSince;
    /**
     * @var Chronos|null
     * @ORM\Column(name="valid_until", type="chronos_datetime", nullable=true)
     */
    private $validUntil;
    /**
     * @var integer
     * @ORM\Column(name="max_visits", type="integer", nullable=true)
     */
    private $maxVisits;

    public function __construct(string $longUrl, ShortUrlMeta $meta = null)
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
}
