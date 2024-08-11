<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Tag\Entity;

use Doctrine\Common\Collections;
use JsonSerializable;
use Shlinkio\Shlink\Common\Entity\AbstractEntity;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;

class Tag extends AbstractEntity implements JsonSerializable
{
    /** @var Collections\Collection<int, ShortUrl> */
    private Collections\Collection $shortUrls;

    public function __construct(private string $name)
    {
        $this->shortUrls = new Collections\ArrayCollection();
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
