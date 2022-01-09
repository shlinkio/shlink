<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Tag\Paginator\Adapter;

use Happyr\DoctrineSpecification\Spec;
use Shlinkio\Shlink\Rest\ApiKey\Spec\WithApiKeySpecsEnsuringJoin;

class TagsPaginatorAdapter extends AbstractTagsPaginatorAdapter
{
    public function getSlice(int $offset, int $length): iterable
    {
        $conditions = [
            new WithApiKeySpecsEnsuringJoin($this->apiKey),
            Spec::orderBy(
                'name', // Ordering by other fields makes no sense here
                $this->params->orderBy()->orderDirection(),
            ),
            Spec::limit($length),
            Spec::offset($offset),
        ];

        $searchTerm = $this->params->searchTerm();
        if ($searchTerm !== null) {
            $conditions[] = Spec::like('name', $searchTerm);
        }

        return $this->repo->match(Spec::andX(...$conditions));
    }
}
