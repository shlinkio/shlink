<?php
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
    protected $key;
    /**
     * @var \DateTime
     * @ORM\Column(name="expiration_date", nullable=true, type="datetime")
     */
    protected $expirationDate;
    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $enabled;

    public function __construct()
    {
        $this->enabled = true;
        $this->key = $this->generateV4Uuid();
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param string $key
     * @return $this
     */
    public function setKey($key)
    {
        $this->key = $key;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getExpirationDate()
    {
        return $this->expirationDate;
    }

    /**
     * @param \DateTime $expirationDate
     * @return $this
     */
    public function setExpirationDate($expirationDate)
    {
        $this->expirationDate = $expirationDate;
        return $this;
    }

    /**
     * @return bool
     */
    public function isExpired()
    {
        if (! isset($this->expirationDate)) {
            return false;
        }

        return $this->expirationDate < new \DateTime();
    }

    /**
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param boolean $enabled
     * @return $this
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
        return $this;
    }

    /**
     * Disables this API key
     *
     * @return $this
     */
    public function disable()
    {
        return $this->setEnabled(false);
    }

    /**
     * Tells if this api key is enabled and not expired
     *
     * @return bool
     */
    public function isValid()
    {
        return $this->isEnabled() && ! $this->isExpired();
    }

    /**
     * The string repesentation of an API key is the key itself
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getKey();
    }
}
