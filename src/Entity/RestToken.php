<?php
namespace Acelaya\UrlShortener\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class RestToken
 * @author
 * @link
 *
 * @ORM\Entity()
 * @ORM\Table(name="rest_tokens")
 */
class RestToken extends AbstractEntity
{
    /**
     * The default interval is 20 minutes
     */
    const DEFAULT_INTERVAL = 'PT20M';

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", name="expiration_date", nullable=false)
     */
    protected $expirationDate;
    /**
     * @var string
     * @ORM\Column(nullable=false)
     */
    protected $token;

    public function __construct()
    {
        $this->updateExpiration();
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
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $token
     * @return $this
     */
    public function setToken($token)
    {
        $this->token = $token;
        return $this;
    }

    /**
     * @return bool
     */
    public function isExpired()
    {
        return new \DateTime() > $this->expirationDate;
    }

    /**
     * Updates the expiration of the token, setting it to the default interval in the future
     * @return $this
     */
    public function updateExpiration()
    {
        return $this->setExpirationDate((new \DateTime())->add(new \DateInterval(self::DEFAULT_INTERVAL)));
    }
}
