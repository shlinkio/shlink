<?php
namespace Acelaya\UrlShortener\Service;

use Acelaya\UrlShortener\Entity\ShortUrl;

interface ShortUrlServiceInterface
{
    /**
     * @return ShortUrl[]
     */
    public function listShortUrls();
}
