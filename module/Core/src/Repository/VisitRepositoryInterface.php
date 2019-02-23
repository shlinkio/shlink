<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Repository;

use Doctrine\Common\Persistence\ObjectRepository;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Entity\Visit;

interface VisitRepositoryInterface extends ObjectRepository
{
    public const DEFAULT_BLOCK_SIZE = 10000;

    /**
     * This method will allow you to iterate the whole list of unlocated visits, but loading them into memory in
     * smaller blocks of a specific size.
     * This will have side effects if you update those rows while you iterate them.
     * If you plan to do so, pass the first argument as false in order to disable applying offsets while slicing the
     * dataset
     *
     * @return iterable|Visit[]
     */
    public function findUnlocatedVisits(bool $applyOffset = true, int $blockSize = self::DEFAULT_BLOCK_SIZE): iterable;

    /**
     * @return Visit[]
     */
    public function findVisitsByShortCode(
        string $shortCode,
        ?DateRange $dateRange = null,
        ?int $limit = null,
        ?int $offset = null
    ): array;

    public function countVisitsByShortCode(string $shortCode, ?DateRange $dateRange = null): int;
}
