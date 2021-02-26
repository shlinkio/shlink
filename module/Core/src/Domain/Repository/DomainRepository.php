<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Domain\Repository;

use Doctrine\ORM\Query\Expr\Join;
use Happyr\DoctrineSpecification\Repository\EntitySpecificationRepository;
use Shlinkio\Shlink\Core\Entity\Domain;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class DomainRepository extends EntitySpecificationRepository implements DomainRepositoryInterface
{
    /**
     * @return Domain[]
     */
    public function findDomainsWithout(?string $excludedAuthority, ?ApiKey $apiKey = null): array
    {
        $qb = $this->createQueryBuilder('d');
        $qb->join(ShortUrl::class, 's', Join::WITH, 's.domain = d')
           ->orderBy('d.authority', 'ASC');

        if ($excludedAuthority !== null) {
            $qb->where($qb->expr()->neq('d.authority', ':excludedAuthority'))
               ->setParameter('excludedAuthority', $excludedAuthority);
        }

        if ($apiKey !== null) {
            $this->applySpecification($qb, $apiKey->spec(), 's');
        }

        return $qb->getQuery()->getResult();
    }
}
