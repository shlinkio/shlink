<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Repository;

use Doctrine\Persistence\ObjectRepository;
use Happyr\DoctrineSpecification\Repository\EntitySpecificationRepositoryInterface;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Persistence\OrphanVisitsCountFiltering;
use Shlinkio\Shlink\Core\Visit\Persistence\OrphanVisitsListFiltering;
use Shlinkio\Shlink\Core\Visit\Persistence\VisitsCountFiltering;
use Shlinkio\Shlink\Core\Visit\Persistence\VisitsListFiltering;
use Shlinkio\Shlink\Core\Visit\Persistence\WithDomainVisitsCountFiltering;
use Shlinkio\Shlink\Core\Visit\Persistence\WithDomainVisitsListFiltering;

/**
 * @extends ObjectRepository<Visit>
 */
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
    public function findVisitsByTag(string $tag, WithDomainVisitsListFiltering $filtering): array;

    public function countVisitsByTag(string $tag, WithDomainVisitsCountFiltering $filtering): int;

    /**
     * @return Visit[]
     */
    public function findVisitsByDomain(string $domain, VisitsListFiltering $filtering): array;

    public function countVisitsByDomain(string $domain, VisitsCountFiltering $filtering): int;

    /**
     * @return Visit[]
     */
    public function findOrphanVisits(OrphanVisitsListFiltering $filtering): array;

    public function countOrphanVisits(OrphanVisitsCountFiltering $filtering): int;

    /**
     * @return Visit[]
     */
    public function findNonOrphanVisits(WithDomainVisitsListFiltering $filtering): array;

    public function countNonOrphanVisits(WithDomainVisitsCountFiltering $filtering): int;

    public function findMostRecentOrphanVisit(): Visit|null;
}
