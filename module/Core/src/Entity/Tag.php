<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Entity;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
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
class Tag extends AbstractEntity implements JsonSerializable
{
    /**
     * @var string
     * @ORM\Column(unique=true)
     */
    private $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function rename(string $name): void
    {
        $this->name = $name;
    }

    public function jsonSerialize(): string
    {
        return $this->name;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
