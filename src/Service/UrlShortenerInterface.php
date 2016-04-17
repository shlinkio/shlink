<?php
namespace Acelaya\UrlShortener\Service;

use Acelaya\UrlShortener\Exception\InvalidUrlException;
use Acelaya\UrlShortener\Exception\RuntimeException;
use Psr\Http\Message\UriInterface;

interface UrlShortenerInterface
{
    /**
     * @param UriInterface $url
     * @return string
     * @throws InvalidUrlException
     * @throws RuntimeException
     */
    public function urlToShortCode(UriInterface $url);

    /**
     * @param string $shortCode
     * @return string
     */
    public function shortCodeToUrl($shortCode);
}
