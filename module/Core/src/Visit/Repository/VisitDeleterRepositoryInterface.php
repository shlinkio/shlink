<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Repository;

use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;

interface VisitDeleterRepositoryInterface
{
    public function deleteShortUrlVisits(ShortUrl $shortUrl): int;

    public function deleteOrphanVisits(): int;
}
