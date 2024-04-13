<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Repository;

use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;

interface VisitIterationRepositoryInterface
{
    public const DEFAULT_BLOCK_SIZE = 10000;

    /**
     * @return iterable<Visit>
     */
    public function findUnlocatedVisits(int $blockSize = self::DEFAULT_BLOCK_SIZE): iterable;

    /**
     * @return iterable<Visit>
     */
    public function findVisitsWithEmptyLocation(int $blockSize = self::DEFAULT_BLOCK_SIZE): iterable;

    /**
     * @return iterable<Visit>
     */
    public function findAllVisits(?DateRange $dateRange = null, int $blockSize = self::DEFAULT_BLOCK_SIZE): iterable;
}
