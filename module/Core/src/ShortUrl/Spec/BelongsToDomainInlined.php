<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Spec;

use Doctrine\ORM\QueryBuilder;
use Happyr\DoctrineSpecification\Filter\Filter;

class BelongsToDomainInlined implements Filter
{
    public function __construct(private string $domainId)
    {
    }

    public function getFilter(QueryBuilder $qb, string $context): string
    {
        // Parameters in this query need to be inlined, not bound, as we need to use it as sub-query later
        $conn = $qb->getEntityManager()->getConnection();
        return $qb->expr()->eq('s.domain', $conn->quote($this->domainId))->__toString();
    }
}
