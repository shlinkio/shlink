<?php
namespace Shlinkio\Shlink\Core\Service;

use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\InvalidShortCodeException;
use Zend\Paginator\Paginator;

interface ShortUrlServiceInterface
{
    /**
     * @param int $page
     * @param string $searchQuery
     * @param array $tags
     * @param null $orderBy
     * @return ShortUrl[]|Paginator
     */
    public function listShortUrls($page = 1, $searchQuery = null, array $tags = [], $orderBy = null);

    /**
     * @param string $shortCode
     * @param string[] $tags
     * @return ShortUrl
     * @throws InvalidShortCodeException
     */
    public function setTagsByShortCode($shortCode, array $tags = []);
}
