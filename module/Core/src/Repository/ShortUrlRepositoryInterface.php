<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Repository;

use Doctrine\Persistence\ObjectRepository;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Entity\ShortUrl;

interface ShortUrlRepositoryInterface extends ObjectRepository
{
    /**
     * @param string|array|null $orderBy
     */
    public function findList(
        ?int $limit = null,
        ?int $offset = null,
        ?string $searchTerm = null,
        array $tags = [],
        $orderBy = null,
        ?DateRange $dateRange = null
    ): array;

    public function countList(?string $searchTerm = null, array $tags = [], ?DateRange $dateRange = null): int;

    public function findOneByShortCode(string $shortCode, ?string $domain = null): ?ShortUrl;

    public function shortCodeIsInUse(string $slug, ?string $domain): bool;
}
