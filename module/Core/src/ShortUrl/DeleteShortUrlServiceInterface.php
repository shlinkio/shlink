<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl;

use Shlinkio\Shlink\Core\Exception;
use Shlinkio\Shlink\Core\ShortUrl\Model\ExpiredShortUrlsConditions;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

interface DeleteShortUrlServiceInterface
{
    /**
     * @throws Exception\ShortUrlNotFoundException
     * @throws Exception\DeleteShortUrlException
     */
    public function deleteByShortCode(
        ShortUrlIdentifier $identifier,
        bool $ignoreThreshold = false,
        ?ApiKey $apiKey = null,
    ): void;

    /**
     * Deletes short URLs that are considered expired based on provided conditions
     */
    public function deleteExpiredShortUrls(ExpiredShortUrlsConditions $conditions): int;

    /**
     * Counts short URLs that are considered expired based on provided conditions, without really deleting them
     */
    public function countExpiredShortUrls(ExpiredShortUrlsConditions $conditions): int;
}
