<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Spec;

use Doctrine\ORM\QueryBuilder;
use Happyr\DoctrineSpecification\Filter\Filter;

class BelongsToDomainInlined implements Filter
{
    private string $domainId;

    public function __construct(string $domainId)
    {
        $this->domainId = $domainId;
    }

    public function getFilter(QueryBuilder $qb, string $dqlAlias): string
    {
        // Parameters in this query need to be inlined, not bound, as we need to use it as sub-query later
        return (string) $qb->expr()->eq('s.domain', '\'' . $this->domainId . '\'');
    }
}
