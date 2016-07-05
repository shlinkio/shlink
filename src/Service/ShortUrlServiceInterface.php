<?php
namespace Acelaya\UrlShortener\Service;

use Acelaya\UrlShortener\Entity\ShortUrl;
use Zend\Paginator\Paginator;

interface ShortUrlServiceInterface
{
    /**
     * @param int $page
     * @return ShortUrl[]|Paginator
     */
    public function listShortUrls($page = 1);
}
