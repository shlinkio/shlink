<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Model;

final class CreateShortUrlData
{
    private string $longUrl;
    private array $tags;
    private ShortUrlMeta $meta;

    public function __construct(string $longUrl, array $tags = [], ?ShortUrlMeta $meta = null)
    {
        $this->longUrl = $longUrl;
        $this->tags = $tags;
        $this->meta = $meta ?? ShortUrlMeta::createEmpty();
    }

    public function getLongUrl(): string
    {
        return $this->longUrl;
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
