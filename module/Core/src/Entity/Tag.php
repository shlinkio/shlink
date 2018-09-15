<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Entity;

use Doctrine\ORM\Mapping as ORM;
use Shlinkio\Shlink\Common\Entity\AbstractEntity;
use Shlinkio\Shlink\Core\Repository\TagRepository;

/**
 * Class Tag
 * @author
 * @link
 *
 * @ORM\Entity(repositoryClass=TagRepository::class)
 * @ORM\Table(name="tags")
 */
class Tag extends AbstractEntity implements \JsonSerializable
{
    /**
     * @var string
     * @ORM\Column(unique=true)
     */
    private $name;

    public function __construct($name = null)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
        return $this;
    }

    public function jsonSerialize(): string
    {
        return $this->name;
    }
}
