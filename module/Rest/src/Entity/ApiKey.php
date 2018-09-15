<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Entity;

use Doctrine\ORM\Mapping as ORM;
use Shlinkio\Shlink\Common\Entity\AbstractEntity;
use Shlinkio\Shlink\Common\Util\StringUtilsTrait;

/**
 * Class ApiKey
 * @author Shlink
 * @link http://shlink.io
 *
 * @ORM\Entity()
 * @ORM\Table(name="api_keys")
 */
class ApiKey extends AbstractEntity
{
    use StringUtilsTrait;

    /**
     * @var string
     * @ORM\Column(name="`key`", nullable=false, unique=true)
     */
    private $key;
    /**
     * @var \DateTime|null
     * @ORM\Column(name="expiration_date", nullable=true, type="datetime")
     */
    private $expirationDate;
    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private $enabled;

    public function __construct()
    {
        $this->enabled = true;
        $this->key = $this->generateV4Uuid();
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): self
    {
        $this->key = $key;
        return $this;
    }

    public function getExpirationDate(): ?\DateTime
    {
        return $this->expirationDate;
    }

    public function setExpirationDate(\DateTime $expirationDate): self
    {
        $this->expirationDate = $expirationDate;
        return $this;
    }

    public function isExpired(): bool
    {
        if ($this->expirationDate === null) {
            return false;
        }

        return $this->expirationDate < new \DateTime();
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;
        return $this;
    }

    public function disable(): self
    {
        return $this->setEnabled(false);
    }

    /**
     * Tells if this api key is enabled and not expired
     */
    public function isValid(): bool
    {
        return $this->isEnabled() && ! $this->isExpired();
    }

    public function __toString(): string
    {
        return $this->getKey();
    }
}
