<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Tag\Model;

use JsonSerializable;

final class TagInfo implements JsonSerializable
{
    public function __construct(
        public readonly string $tag,
        public readonly int $shortUrlsCount,
        public readonly int $visitsCount,
    ) {
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
