<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Tag\Model;

use JsonSerializable;
use Shlinkio\Shlink\Core\Entity\Tag;

final class TagInfo implements JsonSerializable
{
    private Tag $tag;
    private int $shortUrlsCount;
    private int $visitsCount;

    public function __construct(Tag $tag, int $shortUrlsCount, int $visitsCount)
    {
        $this->tag = $tag;
        $this->shortUrlsCount = $shortUrlsCount;
        $this->visitsCount = $visitsCount;
    }

    public function tag(): Tag
    {
        return $this->tag;
    }

    public function shortUrlsCount(): int
    {
        return $this->shortUrlsCount;
    }

    public function visitsCount(): int
    {
        return $this->visitsCount;
    }

    public function jsonSerialize(): array
    {
        return [
            'tag' => $this->tag,
            'shortUrlsCount' => $this->shortUrlsCount,
            'visitsCount' => $this->visitsCount,
        ];
    }
}
