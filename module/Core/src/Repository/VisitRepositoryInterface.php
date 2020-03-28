<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Repository;

use Doctrine\Persistence\ObjectRepository;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Entity\Visit;

interface VisitRepositoryInterface extends ObjectRepository
{
    public const DEFAULT_BLOCK_SIZE = 10000;

    /**
     * @return iterable|Visit[]
     */
    public function findUnlocatedVisits(int $blockSize = self::DEFAULT_BLOCK_SIZE): iterable;

    /**
     * @return iterable|Visit[]
     */
    public function findVisitsWithEmptyLocation(int $blockSize = self::DEFAULT_BLOCK_SIZE): iterable;

    /**
     * @return iterable|Visit[]
     */
    public function findAllVisits(int $blockSize = self::DEFAULT_BLOCK_SIZE): iterable;

    /**
     * @return Visit[]
     */
    public function findVisitsByShortCode(
        string $shortCode,
        ?string $domain = null,
        ?DateRange $dateRange = null,
        ?int $limit = null,
        ?int $offset = null
    ): array;

    public function countVisitsByShortCode(
        string $shortCode,
        ?string $domain = null,
        ?DateRange $dateRange = null
    ): int;
}
