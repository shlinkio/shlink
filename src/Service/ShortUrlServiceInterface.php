<?php
namespace Acelaya\UrlShortener\Service;

use Acelaya\UrlShortener\Entity\ShortUrl;
use Zend\Paginator\Paginator;

interface ShortUrlServiceInterface
{
    /**
     * @return Paginator|ShortUrl[]
     */
    public function listShortUrls();
}
