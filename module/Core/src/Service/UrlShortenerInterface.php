<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service;

use Cake\Chronos\Chronos;
use Psr\Http\Message\UriInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\EntityDoesNotExistException;
use Shlinkio\Shlink\Core\Exception\InvalidShortCodeException;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Core\Exception\NonUniqueSlugException;
use Shlinkio\Shlink\Core\Exception\RuntimeException;

interface UrlShortenerInterface
{
    /**
     * @throws NonUniqueSlugException
     * @throws InvalidUrlException
     * @throws RuntimeException
     */
    public function urlToShortCode(
        UriInterface $url,
        array $tags = [],
        ?Chronos $validSince = null,
        ?Chronos $validUntil = null,
        ?string $customSlug = null,
        ?int $maxVisits = null
    ): ShortUrl;

    /**
     * Tries to find the mapped URL for provided short code. Returns null if not found
     *
     * @throws InvalidShortCodeException
     * @throws EntityDoesNotExistException
     */
    public function shortCodeToUrl(string $shortCode): ShortUrl;
}
