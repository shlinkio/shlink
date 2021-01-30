<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Model;

final class CreateShortUrlData
{
    private array $tags;
    private ShortUrlMeta $meta;

    public function __construct(array $tags = [], ?ShortUrlMeta $meta = null)
    {
        $this->tags = $tags;
        $this->meta = $meta ?? ShortUrlMeta::createEmpty();
    }

    /**
     * @return string[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    public function getMeta(): ShortUrlMeta
    {
        return $this->meta;
    }
}
