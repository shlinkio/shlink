<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Entity;

use JsonSerializable;
use Shlinkio\Shlink\Common\Entity\AbstractEntity;

class Tag extends AbstractEntity implements JsonSerializable
{
    /** @var string */
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
