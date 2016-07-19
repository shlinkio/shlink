<?php
namespace Shlinkio\Shlink\Core\Service;

use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Zend\Paginator\Paginator;

interface ShortUrlServiceInterface
{
    /**
     * @param int $page
     * @return ShortUrl[]|Paginator
     */
    public function listShortUrls($page = 1);
}
