<?php
namespace Shlinkio\Shlink\Core\Service;

use Psr\Http\Message\UriInterface;
use Shlinkio\Shlink\Core\Exception\InvalidShortCodeException;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Core\Exception\RuntimeException;

interface UrlShortenerInterface
{
    /**
     * Creates and persists a unique shortcode generated for provided url
     *
     * @param UriInterface $url
     * @return string
     * @throws InvalidUrlException
     * @throws RuntimeException
     */
    public function urlToShortCode(UriInterface $url);

    /**
     * Tries to find the mapped URL for provided short code. Returns null if not found
     *
     * @param string $shortCode
     * @return string|null
     * @throws InvalidShortCodeException
     */
    public function shortCodeToUrl($shortCode);
}
