<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Repository;

use Shlinkio\Shlink\Core\ShortUrl\Model\ExpiredShortUrlsConditions;

interface ExpiredShortUrlsRepositoryInterface
{
    /**
     * Delete expired short URLs matching provided conditions
     */
    public function delete(ExpiredShortUrlsConditions $conditions = new ExpiredShortUrlsConditions()): int;

    /**
     * Count how many expired short URLs would be deleted for provided conditions
     */
    public function dryCount(ExpiredShortUrlsConditions $conditions = new ExpiredShortUrlsConditions()): int;
}
