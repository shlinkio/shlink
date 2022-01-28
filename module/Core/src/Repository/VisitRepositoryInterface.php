<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Repository;

use Doctrine\Persistence\ObjectRepository;
use Happyr\DoctrineSpecification\Repository\EntitySpecificationRepositoryInterface;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Visit\Persistence\VisitsCountFiltering;
use Shlinkio\Shlink\Core\Visit\Persistence\VisitsListFiltering;

// TODO Split into VisitsListsRepository and VisitsLocationRepository
interface VisitRepositoryInterface extends ObjectRepository, EntitySpecificationRepositoryInterface
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
    public function findVisitsByShortCode(ShortUrlIdentifier $identifier, VisitsListFiltering $filtering): array;

    public function countVisitsByShortCode(ShortUrlIdentifier $identifier, VisitsCountFiltering $filtering): int;

    /**
     * @return Visit[]
     */
    public function findVisitsByTag(string $tag, VisitsListFiltering $filtering): array;

    public function countVisitsByTag(string $tag, VisitsCountFiltering $filtering): int;

    /**
     * @return Visit[]
     */
    public function findOrphanVisits(VisitsListFiltering $filtering): array;

    public function countOrphanVisits(VisitsCountFiltering $filtering): int;

    /**
     * @return Visit[]
     */
    public function findNonOrphanVisits(VisitsListFiltering $filtering): array;

    public function countNonOrphanVisits(VisitsCountFiltering $filtering): int;
}
