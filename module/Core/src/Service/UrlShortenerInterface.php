<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service;

use Psr\Http\Message\UriInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\EntityDoesNotExistException;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Core\Exception\NonUniqueSlugException;
use Shlinkio\Shlink\Core\Exception\RuntimeException;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;

interface UrlShortenerInterface
{
    /**
     * @param string[] $tags
     * @throws NonUniqueSlugException
     * @throws InvalidUrlException
     * @throws RuntimeException
     */
    public function urlToShortCode(UriInterface $url, array $tags, ShortUrlMeta $meta): ShortUrl;

    /**
     * @throws EntityDoesNotExistException
     */
    public function shortCodeToUrl(string $shortCode, ?string $domain = null): ShortUrl;
}
