<?php
namespace Acelaya\UrlShortener\Service;

use Acelaya\UrlShortener\Exception\InvalidShortCodeException;
use Acelaya\UrlShortener\Exception\InvalidUrlException;
use Acelaya\UrlShortener\Exception\RuntimeException;
use Psr\Http\Message\UriInterface;

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
