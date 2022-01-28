<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Tag\Paginator\Adapter;

use Happyr\DoctrineSpecification\Spec;
use Pagerfanta\Adapter\AdapterInterface;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Repository\TagRepositoryInterface;
use Shlinkio\Shlink\Core\Tag\Model\TagsParams;
use Shlinkio\Shlink\Rest\ApiKey\Spec\WithApiKeySpecsEnsuringJoin;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

abstract class AbstractTagsPaginatorAdapter implements AdapterInterface
{
    public function __construct(
        protected TagRepositoryInterface $repo,
        protected TagsParams $params,
        protected ?ApiKey $apiKey,
    ) {
    }

    public function getNbResults(): int
    {
        $conditions = [
            // FIXME I don't think using Spec::selectNew is the correct thing in this context.
            //       Ideally it should be Spec::select, but seems to be the only way to use Spec::COUNT(...).
            Spec::selectNew(Tag::class, Spec::COUNT('id', true)),
            new WithApiKeySpecsEnsuringJoin($this->apiKey),
        ];

        $searchTerm = $this->params->searchTerm();
        if ($searchTerm !== null) {
            $conditions[] = Spec::like('name', $searchTerm);
        }

        return (int) $this->repo->matchSingleScalarResult(Spec::andX(...$conditions));
    }
}
