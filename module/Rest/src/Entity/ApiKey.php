<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Entity;

use Cake\Chronos\Chronos;
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
     * @var Chronos|null
     * @ORM\Column(name="expiration_date", nullable=true, type="chronos_datetime")
     */
    private $expirationDate;
    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private $enabled;

    public function __construct(?Chronos $expirationDate = null)
    {
        $this->key = $this->generateV4Uuid();
        $this->expirationDate = $expirationDate;
        $this->enabled = true;
    }

    public function getExpirationDate(): ?Chronos
    {
        return $this->expirationDate;
    }

    public function isExpired(): bool
    {
        if ($this->expirationDate === null) {
            return false;
        }

        return $this->expirationDate->lt(Chronos::now());
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function disable(): self
    {
        $this->enabled = false;
        return $this;
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
        return $this->key;
    }
}
