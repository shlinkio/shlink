<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Domain\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Shlinkio\Shlink\Core\Entity\Domain;
use Shlinkio\Shlink\Core\Entity\ShortUrl;

class DomainRepository extends EntityRepository implements DomainRepositoryInterface
{
    /**
     * @return Domain[]
     */
    public function findDomainsWithout(?string $excludedAuthority = null): array
    {
        $qb = $this->createQueryBuilder('d');
        $qb->join(ShortUrl::class, 's', Join::WITH, $qb->expr()->eq('s.domain', 'd'))
           ->orderBy('d.authority', 'ASC');

        if ($excludedAuthority !== null) {
            $qb->where($qb->expr()->neq('d.authority', ':excludedAuthority'))
               ->setParameter('excludedAuthority', $excludedAuthority);
        }

        return $qb->getQuery()->getResult();
    }
}
