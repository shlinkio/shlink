<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service;

use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\InvalidShortCodeException;
use Shlinkio\Shlink\Core\Model\ShortCodeMeta;
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
    public function setTagsByShortCode(string $shortCode, array $tags = []): ShortUrl;

    /**
     * @param string $shortCode
     * @param ShortCodeMeta $shortCodeMeta
     * @return ShortUrl
     * @throws InvalidShortCodeException
     */
    public function updateMetadataByShortCode(string $shortCode, ShortCodeMeta $shortCodeMeta): ShortUrl;
}
