<?php
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
     * ShortUrl constructor.
     */
    public function __construct()
    {
        $this->setDateCreated(new \DateTime());
        $this->setVisits(new ArrayCollection());
        $this->setShortCode('');
    }

    /**
     * @return string
     */
    public function getOriginalUrl()
    {
        return $this->originalUrl;
    }

    /**
     * @param string $originalUrl
     * @return $this
     */
    public function setOriginalUrl($originalUrl)
    {
        $this->originalUrl = (string) $originalUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getShortCode()
    {
        return $this->shortCode;
    }

    /**
     * @param string $shortCode
     * @return $this
     */
    public function setShortCode($shortCode)
    {
        $this->shortCode = $shortCode;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDateCreated()
    {
        return $this->dateCreated;
    }

    /**
     * @param \DateTime $dateCreated
     * @return $this
     */
    public function setDateCreated($dateCreated)
    {
        $this->dateCreated = $dateCreated;
        return $this;
    }

    /**
     * @return Visit[]|Collection
     */
    public function getVisits()
    {
        return $this->visits;
    }

    /**
     * @param Visit[]|Collection $visits
     * @return $this
     */
    public function setVisits($visits)
    {
        $this->visits = $visits;
        return $this;
    }

    /**
     * @return Collection|Tag[]
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param Collection|Tag[] $tags
     * @return $this
     */
    public function setTags($tags)
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
            'dateCreated' => isset($this->dateCreated) ? $this->dateCreated->format(\DateTime::ISO8601) : null,
            'visitsCount' => count($this->visits),
        ];
    }
}
