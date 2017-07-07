<?php
namespace Shlinkio\Shlink\Core\Entity;

use Doctrine\ORM\Mapping as ORM;
use Shlinkio\Shlink\Common\Entity\AbstractEntity;

/**
 * Class Tag
 * @author
 * @link
 *
 * @ORM\Entity()
 * @ORM\Table(name="tags")
 */
class Tag extends AbstractEntity implements \JsonSerializable
{
    /**
     * @var string
     * @ORM\Column(unique=true)
     */
    protected $name;

    public function __construct($name = null)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
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
        return $this->name;
    }
}
