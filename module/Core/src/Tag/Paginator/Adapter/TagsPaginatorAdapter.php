<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Tag\Paginator\Adapter;

use Happyr\DoctrineSpecification\Spec;
use Shlinkio\Shlink\Rest\ApiKey\Spec\WithApiKeySpecsEnsuringJoin;

class TagsPaginatorAdapter extends AbstractTagsPaginatorAdapter
{
    public function getSlice(int $offset, int $length): iterable
    {
        return $this->repo->match(Spec::andX(
            new WithApiKeySpecsEnsuringJoin($this->apiKey),
            Spec::orderBy('name'),
            Spec::limit($length),
            Spec::offset($offset),
        ));
    }
}
