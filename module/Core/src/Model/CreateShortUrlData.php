<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Model;

use Psr\Http\Message\UriInterface;

final class CreateShortUrlData
{
    /** @var UriInterface */
    private $longUrl;
    /** @var array */
    private $tags;
    /** @var ShortUrlMeta */
    private $meta;

    public function __construct(
        UriInterface $longUrl,
        array $tags = [],
        ?ShortUrlMeta $meta = null
    ) {
        $this->longUrl = $longUrl;
        $this->tags = $tags;
        $this->meta = $meta ?? ShortUrlMeta::createEmpty();
    }

    /**
     * @return UriInterface
     */
    public function getLongUrl(): UriInterface
    {
        return $this->longUrl;
    }

    /**
     * @return array
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @return ShortUrlMeta
     */
    public function getMeta(): ShortUrlMeta
    {
        return $this->meta;
    }
}
