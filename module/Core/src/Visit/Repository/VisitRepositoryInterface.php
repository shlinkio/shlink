<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Repository;

use Doctrine\Persistence\ObjectRepository;
use Happyr\DoctrineSpecification\Repository\EntitySpecificationRepositoryInterface;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Persistence\VisitsCountFiltering;
use Shlinkio\Shlink\Core\Visit\Persistence\VisitsListFiltering;

interface VisitRepositoryInterface extends ObjectRepository, EntitySpecificationRepositoryInterface
{
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
    public function findVisitsByDomain(string $domain, VisitsListFiltering $filtering): array;

    public function countVisitsByDomain(string $domain, VisitsCountFiltering $filtering): int;

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

    public function findMostRecentOrphanVisit(): ?Visit;
}
