<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Repository;

use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlWithVisitsSummary;
use Shlinkio\Shlink\Core\ShortUrl\Persistence\ShortUrlsCountFiltering;
use Shlinkio\Shlink\Core\ShortUrl\Persistence\ShortUrlsListFiltering;

interface ShortUrlListRepositoryInterface
{
    /**
     * @return ShortUrlWithVisitsSummary[]
     */
    public function findList(ShortUrlsListFiltering $filtering): array;

    public function countList(ShortUrlsCountFiltering $filtering): int;
}
