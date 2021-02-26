<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Repository;

use Doctrine\Persistence\ObjectRepository;
use Happyr\DoctrineSpecification\Repository\EntitySpecificationRepositoryInterface;
use Happyr\DoctrineSpecification\Specification\Specification;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

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
    public function findVisitsByShortCode(
        string $shortCode,
        ?string $domain = null,
        ?DateRange $dateRange = null,
        ?int $limit = null,
        ?int $offset = null,
        ?Specification $spec = null
    ): array;

    public function countVisitsByShortCode(
        string $shortCode,
        ?string $domain = null,
        ?DateRange $dateRange = null,
        ?Specification $spec = null
    ): int;

    /**
     * @return Visit[]
     */
    public function findVisitsByTag(
        string $tag,
        ?DateRange $dateRange = null,
        ?int $limit = null,
        ?int $offset = null,
        ?Specification $spec = null
    ): array;

    public function countVisitsByTag(string $tag, ?DateRange $dateRange = null, ?Specification $spec = null): int;

    /**
     * @return Visit[]
     */
    public function findOrphanVisits(?DateRange $dateRange = null, ?int $limit = null, ?int $offset = null): array;

    public function countOrphanVisits(?DateRange $dateRange = null): int;

    public function countVisits(?ApiKey $apiKey = null): int;
}
