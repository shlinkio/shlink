<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Shlinkio\Shlink\Common\Entity\AbstractEntity;

/**
 * Class ShortUrl
 * @author
 * @link
 *
 * @ORM\Entity(repositoryClass="Shlinkio\Shlink\Core\Repository\ShortUrlRepository")
 * @ORM\Table(name="short_urls")
 */
class ShortUrl extends AbstractEntity implements \JsonSerializable
{
    /**
     * @var string
     * @ORM\Column(name="original_url", type="string", nullable=false, length=1024)
     */
    protected $originalUrl;
    /**
     * @var string
     * @ORM\Column(
     *     name="short_code",
     *     type="string",
     *     nullable=false,
     *     length=10,
     *     unique=true
     * )
     */
    protected $shortCode;
    /**
     * @var \DateTime
     * @ORM\Column(name="date_created", type="datetime")
     */
    protected $dateCreated;
    /**
     * @var Collection|Visit[]
     * @ORM\OneToMany(targetEntity=Visit::class, mappedBy="shortUrl", fetch="EXTRA_LAZY")
     */
    protected $visits;
    /**
     * @var Collection|Tag[]
     * @ORM\ManyToMany(targetEntity=Tag::class, cascade={"persist"})
     * @ORM\JoinTable(name="short_urls_in_tags", joinColumns={
     *     @ORM\JoinColumn(name="short_url_id", referencedColumnName="id")
     * }, inverseJoinColumns={
     *     @ORM\JoinColumn(name="tag_id", referencedColumnName="id")
     * })
     */
    protected $tags;
    /**
     * @var \DateTime
     * @ORM\Column(name="valid_since", type="datetime", nullable=true)
     */
    protected $validSince;
    /**
     * @var \DateTime
     * @ORM\Column(name="valid_until", type="datetime", nullable=true)
     */
    protected $validUntil;
    /**
     * @var integer
     * @ORM\Column(name="max_visits", type="integer", nullable=true)
     */
    protected $maxVisits;

    /**
     * ShortUrl constructor.
     */
    public function __construct()
    {
        $this->dateCreated = new \DateTime();
        $this->visits = new ArrayCollection();
        $this->shortCode = '';
        $this->tags = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getOriginalUrl(): string
    {
        return $this->originalUrl;
    }

    /**
     * @param string $originalUrl
     * @return $this
     */
    public function setOriginalUrl(string $originalUrl)
    {
        $this->originalUrl = $originalUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getShortCode(): string
    {
        return $this->shortCode;
    }

    /**
     * @param string $shortCode
     * @return $this
     */
    public function setShortCode(string $shortCode)
    {
        $this->shortCode = $shortCode;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDateCreated(): \DateTime
    {
        return $this->dateCreated;
    }

    /**
     * @param \DateTime $dateCreated
     * @return $this
     */
    public function setDateCreated(\DateTime $dateCreated)
    {
        $this->dateCreated = $dateCreated;
        return $this;
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
     * @return $this
     */
    public function setTags(Collection $tags)
    {
        $this->tags = $tags;
        return $this;
    }

    /**
     * @param Tag $tag
     * @return $this
     */
    public function addTag(Tag $tag)
    {
        $this->tags->add($tag);
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getValidSince()
    {
        return $this->validSince;
    }

    /**
     * @param \DateTime|null $validSince
     * @return $this|self
     */
    public function setValidSince($validSince): self
    {
        $this->validSince = $validSince;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getValidUntil()
    {
        return $this->validUntil;
    }

    /**
     * @param \DateTime|null $validUntil
     * @return $this|self
     */
    public function setValidUntil($validUntil): self
    {
        $this->validUntil = $validUntil;
        return $this;
    }

    public function getVisitsCount(): int
    {
        return count($this->visits);
    }

    /**
     * @param Collection $visits
     * @return ShortUrl
     * @internal
     */
    public function setVisits(Collection $visits): self
    {
        $this->visits = $visits;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getMaxVisits()
    {
        return $this->maxVisits;
    }

    /**
     * @param int|null $maxVisits
     * @return $this|self
     */
    public function setMaxVisits($maxVisits): self
    {
        $this->maxVisits = $maxVisits;
        return $this;
    }

    public function maxVisitsReached(): bool
    {
        return $this->maxVisits !== null && $this->getVisitsCount() >= $this->maxVisits;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return [
            'shortCode' => $this->shortCode,
            'originalUrl' => $this->originalUrl,
            'dateCreated' => $this->dateCreated !== null ? $this->dateCreated->format(\DateTime::ATOM) : null,
            'visitsCount' => $this->getVisitsCount(),
            'tags' => $this->tags->toArray(),
        ];
    }
}
