<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Repository;

use Doctrine\Persistence\ObjectRepository;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Entity\Visit;

interface VisitRepositoryInterface extends ObjectRepository
{
    /**
     * This method will allow you to iterate the whole list of unlocated visits, but loading them into memory in
     * smaller blocks of a specific size.
     * This will have side effects if you update those rows while you iterate them, in a way that they are no longer
     * unlocated.
     * If you plan to do so, pass the first argument as false in order to disable applying offsets while slicing the
     * dataset.
     *
     * @return iterable|Visit[]
     */
    public function findUnlocatedVisits(bool $applyOffset = true): iterable;

    /**
     * This method will allow you to iterate the whole list of unlocated visits, but loading them into memory in
     * smaller blocks of a specific size.
     * This will have side effects if you update those rows while you iterate them, in a way that they are no longer
     * unlocated.
     * If you plan to do so, pass the first argument as false in order to disable applying offsets while slicing the
     * dataset.
     *
     * @return iterable|Visit[]
     */
    public function findVisitsWithEmptyLocation(bool $applyOffset = true): iterable;

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
