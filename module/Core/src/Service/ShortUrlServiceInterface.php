<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service;

use Laminas\Paginator\Paginator;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Model\ShortUrlsParams;

interface ShortUrlServiceInterface
{
    /**
     * @return ShortUrl[]|Paginator
     */
    public function listShortUrls(ShortUrlsParams $params): Paginator;

    /**
     * @param string[] $tags
     * @throws ShortUrlNotFoundException
     */
    public function setTagsByShortCode(string $shortCode, array $tags = []): ShortUrl;

    /**
     * @throws ShortUrlNotFoundException
     */
    public function updateMetadataByShortCode(ShortUrlIdentifier $identifier, ShortUrlMeta $shortUrlMeta): ShortUrl;
}
