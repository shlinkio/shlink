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

    public static function fromRawData(array $data): self
    {
        return new self($data['tag'], (int) $data['shortUrlsCount'], (int) $data['visitsCount']);
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
