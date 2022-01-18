<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Spec;

use Doctrine\ORM\QueryBuilder;
use Happyr\DoctrineSpecification\Filter\Filter;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class BelongsToApiKeyInlined implements Filter
{
    public function __construct(private ApiKey $apiKey)
    {
    }

    public function getFilter(QueryBuilder $qb, string $dqlAlias): string
    {
        // Parameters in this query need to be inlined, not bound, as we need to use it as sub-query later
        $conn = $qb->getEntityManager()->getConnection();
        return $qb->expr()->eq('s.authorApiKey', $conn->quote($this->apiKey->getId()))->__toString();
    }
}
