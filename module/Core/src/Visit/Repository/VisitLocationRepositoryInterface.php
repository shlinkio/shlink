<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Repository;

use Shlinkio\Shlink\Core\Visit\Entity\Visit;

interface VisitLocationRepositoryInterface
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
    public function findAllVisits(int $blockSize = self::DEFAULT_BLOCK_SIZE): iterable;
}
